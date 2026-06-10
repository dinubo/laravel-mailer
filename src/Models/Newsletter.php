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

    public static function statistics(?Builder $query = null)
    {
        if ($query == null) {
            $query = Message::query();
        }

        $total_days = 21;

        $start_date = today()->subDays($total_days - 1);

        $send_statistics = (clone $query)
        ->where('send_at', '>=', $start_date->toDateTimeString())
        ->selectRaw('DATE(send_at) AS date, COUNT(*) AS total')
        ->groupBy(DB::raw('DATE(send_at)'))
        ->get()
        ->mapWithKeys(function ($row) {
            return [$row['date'] => $row['total']];
        });

        $open_statistics = (clone $query)
        ->where('open_at', '>=', $start_date->toDateTimeString())
        ->selectRaw('DATE(open_at) AS date, COUNT(*) AS total')
        ->groupBy(DB::raw('DATE(open_at)'))
        ->get()
        ->mapWithKeys(function ($row) {
            return [$row['date'] => $row['total']];
        });

        $click_statistics = (clone $query)
        ->where('click_at', '>=', $start_date->toDateTimeString())
        ->selectRaw('DATE(click_at) AS date, COUNT(*) AS total')
        ->groupBy(DB::raw('DATE(click_at)'))
        ->get()
        ->mapWithKeys(function ($row) {
            return [$row['date'] => $row['total']];
        });

        $unsubscribe_statistics = (clone $query)
        ->where('unsubscribe_at', '>=', $start_date->toDateTimeString())
        ->selectRaw('DATE(unsubscribe_at) AS date, COUNT(*) AS total')
        ->groupBy(DB::raw('DATE(unsubscribe_at)'))
        ->get()
        ->mapWithKeys(function ($row) {
            return [$row['date'] => $row['total']];
        });

        $spam_statistics = (clone $query)
        ->where('spam_at', '>=', $start_date->toDateTimeString())
        ->selectRaw('DATE(spam_at) AS date, COUNT(*) AS total')
        ->groupBy(DB::raw('DATE(spam_at)'))
        ->get()
        ->mapWithKeys(function ($row) {
            return [$row['date'] => $row['total']];
        });

        $bounce_statistics = (clone $query)
        ->where('bounce_at', '>=', $start_date->toDateTimeString())
        ->selectRaw('DATE(bounce_at) AS date, COUNT(*) AS total')
        ->groupBy(DB::raw('DATE(bounce_at)'))
        ->get()
        ->mapWithKeys(function ($row) {
            return [$row['date'] => $row['total']];
        });

        $drop_statistics = (clone $query)
        ->where('drop_at', '>=', $start_date->toDateTimeString())
        ->selectRaw('DATE(drop_at) AS date, COUNT(*) AS total')
        ->groupBy(DB::raw('DATE(drop_at)'))
        ->get()
        ->mapWithKeys(function ($row) {
            return [$row['date'] => $row['total']];
        });

        $data = [];

        for($i = 0; $i < $total_days; $i++) {
            $date = $start_date->copy()->addDays($i)->toDateString();
            $data[$date] = [
                'send' => 0,
                'open' => 0,
                'click' => 0,
                'unsubscribe' => 0,
                'spam' => 0,
                'bounce' => 0,
                'drop' => 0,
            ];
        }

        foreach($send_statistics AS $date => $total) {
            $data[$date]['send'] = $total;
        }

        foreach($open_statistics AS $date => $total) {
            $data[$date]['open'] = $total;
        }

        foreach($click_statistics AS $date => $total) {
            $data[$date]['click'] = $total;
        }

        foreach($unsubscribe_statistics AS $date => $total) {
            $data[$date]['unsubscribe'] = $total;
        }

        foreach($spam_statistics AS $date => $total) {
            $data[$date]['spam'] = $total;
        }

        foreach($bounce_statistics AS $date => $total) {
            $data[$date]['bounce'] = $total;
        }

        foreach($drop_statistics AS $date => $total) {
            $data[$date]['drop'] = $total;
        }

        $data = collect($data);

        $chart_data = [
            'labels' => $data->keys()->toArray(),
            'datasets' => [
                [
                    'label' => "Sent",
                    'data' => $data->pluck('send')->toArray(),
                ],
                [
                    'label' => "Opens",
                    'data' => $data->pluck('open')->toArray(),
                ],
                [
                    'label' => "Clicks",
                    'data' => $data->pluck('click')->toArray(),
                ],
                [
                    'label' => "Unsubscribes",
                    'data' => $data->pluck('unsubscribe')->toArray(),
                ],
                [
                    'label' => "Bounces",
                    'data' => $data->pluck('bounce')->toArray(),
                ],
                [
                    'label' => "Drops",
                    'data' => $data->pluck('drop')->toArray(),
                ],
                [
                    'label' => "Spam",
                    'data' => $data->pluck('spam')->toArray(),
                ],
            ],
        ];

        return $chart_data;
    }

    public function getStatistics()
    {
        return static::statistics($this->mails()->getQuery());
    }
}
