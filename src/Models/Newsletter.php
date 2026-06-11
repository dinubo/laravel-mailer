<?php

namespace Dinubo\Mailer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Dinubo\Mailer\Event;
use Dinubo\Mailer\Jobs\SendNewsletter;
use Dinubo\Mailer\Mailer;

class Newsletter extends Model
{
    use SoftDeletes;

    protected $table = 'mailer_newsletters';

    protected $fillable = [
        'segment',
        'event',
        'action',
        'category',
        'is_active',
        'daily_rate',
        'after_sec',
        'subject',
        'body',
    ];

    protected $casts = [
        //
    ];

    public function mails()
    {
        return $this->morphMany(Message::class, 'mailable');
    }

    public function getAfterAttribute()
    {
        if ($this->after_sec === null) {
            return '-';
        }

        if ($this->after_sec === 0) {
            return 'Immediately';
        }

        $days = floor($this->after_sec / (60 * 60 * 24));
        $hours = floor(($this->after_sec % (60 * 60 * 24)) / (60 * 60));
        $min = floor(($this->after_sec % (60 * 60)) / 60);
        $sec = floor($this->after_sec % 60);

        $string = '';

        if ($days) {
            $string .= ($string == '') ? '' : ' ';
            $string .= $days . ' day' . ($days == 1 ? '' : 's');
        }

        if ($hours) {
            $string .= ($string == '') ? '' : ' ';
            $string .= $hours . ' hour' . ($hours == 1 ? '' : 's');
        }

        if ($min) {
            $string .= ($string == '') ? '' : ' ';
            $string .= $min . ' min';
        }

        if ($sec) {
            $string .= ($string == '') ? '' : ' ';
            $string .= $sec . ' sec';
        }

        return $string;
    }

    public function message(Model $recipient, ?Contact $contact = null): Message
    {
        $contact = $contact ?? Contact::from(
            $recipient->email,
            $recipient
        );

        /** @var Message $message */
        $message = $contact->messages()->make([
            'category' => $this->category,
            'subject' => $this->subject,
        ]);

        $message->mailable()->associate($this);

        $message->receivable()->associate($recipient);

        return $message;
    }

    public function schedule(Model $recipient, ?Carbon $scheduleFor = null, ?Event $event = null, array $args = [])
    {
        $message = $this->message($recipient);

        if ($scheduleFor) {
            $message->schedule_for = $scheduleFor;
        }

        DB::transaction(function () use ($message) {
            $message->save();

            if ($message->schedule_for) {
                $message->scheduled();
            }
        });

        dispatch(new SendNewsletter($message, $event?->id, $args))
            ->afterCommit()
            ->delay($message->schedule_for);
    }

    public function scheduleBy(Event $event, Model $recipient, array $args = [])
    {
        $scheduleFor = $this->after_sec
            ? now()->addSeconds($this->after_sec)
            : null;

        $this->schedule($recipient, $scheduleFor, $event, $args);
    }

    public function processAction(Model $recipient): array
    {
        if (!$this->action) {
            return [];
        }

        return Mailer::action($this->action, $recipient, $this);
    }

    public function buildPlaceholders(Model $recipient): array
    {
        return collect(Mailer::registeredPlaceholders())
            ->map(
                fn($placeholder) => $placeholder->build($recipient)
            )
            ->all();
    }

    public static function statistics(?Builder $query = null, ?string $from = null, ?string $to = null)
    {
        if ($query == null) {
            $query = Message::query();
        }

        $start_date = $from ? Carbon::parse($from)->startOfDay() : today()->subDays(20);
        $end_date = $to ? Carbon::parse($to)->startOfDay() : today();

        // The controller validates from <= to; keep the model self-consistent too.
        if ($end_date->lessThan($start_date)) {
            [$start_date, $end_date] = [$end_date, $start_date];
        }

        $total_days = (int) $start_date->diffInDays($end_date) + 1;

        $range = [$start_date->toDateTimeString(), $end_date->copy()->endOfDay()->toDateTimeString()];

        $metrics = ['send', 'open', 'click', 'unsubscribe', 'bounce', 'drop', 'spam'];

        $labels = [];
        $date_index = [];

        for ($i = 0; $i < $total_days; $i++) {
            $date = $start_date->copy()->addDays($i)->toDateString();
            $labels[] = $date;
            $date_index[$date] = $i;
        }

        // Bucket each message under its Newsletter id; every other mailable type
        // (and null-mailable messages) collapses into a single "other" series, so
        // the payload stays bounded by the number of newsletters. The chart sums
        // every series (= all messages); the per-newsletter table reads its own id.
        $bucket = 'CASE WHEN mailable_type = ? THEN mailable_id ELSE NULL END';

        $series = [];

        foreach ($metrics as $metric) {
            $column = $metric . '_at';

            // Group by the SELECT aliases (not the repeated expressions): MySQL's
            // ONLY_FULL_GROUP_BY rejects matching a CASE expression between the
            // SELECT and GROUP BY lists, but grouping by the alias is unambiguous.
            $rows = (clone $query)
                ->whereBetween($column, $range)
                ->selectRaw("DATE($column) AS date, ($bucket) AS newsletter_id, COUNT(*) AS total", [static::class])
                ->groupByRaw('date, newsletter_id')
                ->get();

            foreach ($rows as $row) {
                if (! isset($date_index[$row->date])) {
                    continue;
                }

                $key = $row->newsletter_id === null ? 'other' : (string) $row->newsletter_id;

                if (! isset($series[$key])) {
                    $series[$key] = array_fill_keys($metrics, array_fill(0, $total_days, 0));
                }

                $series[$key][$metric][$date_index[$row->date]] = (int) $row->total;
            }
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    public function getStatistics(?string $from = null, ?string $to = null)
    {
        return static::statistics($this->mails()->getQuery(), $from, $to);
    }
}
