<?php

namespace Dinubo\Mailer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Contact;
use Dinubo\Mailer\Models\Newsletter;

class SendNewsletters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailer:newsletters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule and queue active newsletters for sending to their recipients.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $newsletters = Newsletter::where('is_active', true)
            ->whereNotNull('segment')
            ->whereNotNull('daily_rate')
            ->get();

        $total_newsletters = $newsletters->count();

        $i = 0;
        foreach($newsletters as $newsletter) {
            $i++;

            $this->info("Sending {$i} of {$total_newsletters}: [{$newsletter->id}] `{$newsletter->subject}`...");

            $this->process($newsletter);
        }
    }

    protected function process(Newsletter $newsletter)
    {
        $segment = Mailer::registeredSegments()[$newsletter->segment] ?? null;

        if (! $segment) {
            throw new \Exception("Segment `$newsletter->segment` not defined.");
        }

        $recipient_model = config('mailer.recipient_model');

        if (!$recipient_model) {
            throw new \Exception('No recipient model configured (mailer.recipient_model).');
        }

        /** @var Builder $recipients */
        $recipients = $recipient_model::query();

        $notAllowed = Contact::query()
            ->whereNotNull('unsubscribe_at')
            ->orWhereNotNull('bounce_at')
            ->orWhereNotNull('spam_at')
            ->select('address');

        // Contact addresses are stored lower-cased; compare case-insensitively so a
        // recipient whose email differs only by case is still suppressed.
        $recipients->whereNotIn(DB::raw('LOWER(email)'), $notAllowed);

        $recipients->whereDoesntHave('mailerMails', function ($query) use ($newsletter) {
            $query
                // already sheduled or sent
                ->where([
                    ['mailable_id', $newsletter->id],
                    ['mailable_type', $newsletter->getMorphClass()],
                ])
                // any newsletter the last x hours
                ->orWhere(function($query) use ($newsletter) {
                    $query->where([
                        ['mailable_type', $newsletter->getMorphClass()],
                        ['send_at', '>', now()->subHours(96)]
                    ]);
                })
                // any newsletter planned for the next hours
                ->orWhere(function($query) use ($newsletter) {
                    $query->where([
                        ['mailable_type', $newsletter->getMorphClass()],
                        ['schedule_for', '>', now()->subHours(12)],
                        ['schedule_for', '<=', now()->addHours(12)]
                    ]);
                })
                ;
        });

        $globalFilter = Mailer::registeredFilter();

        $segmentFilter = $segment->filter;
        
        if ($globalFilter?->query) {
            $recipients->where(function(Builder $query) use ($globalFilter, $newsletter) {
                ($globalFilter->query)($query, $newsletter);
            });
        }

        if ($segmentFilter?->query) {
            $recipients->where(function(Builder $query) use ($segmentFilter, $newsletter) {
                ($segmentFilter->query)($query, $newsletter);
            });
        }

        if ($globalFilter?->collection) {
            $recipients = ($globalFilter->collection)(
                $recipients instanceof Builder ? $recipients->get() : $recipients,
                $newsletter
            );
        }

        if ($segmentFilter?->collection) {
            $recipients = ($segmentFilter->collection)(
                $recipients instanceof Builder ? $recipients->get() : $recipients,
                $newsletter
            );
        }

        $random = $segment->random;

        if ($random && $recipients instanceof Builder) {
            $recipients->inRandomOrder();
        }

        $elapse_min = 15;
        $rate = ceil($newsletter->daily_rate / (24 * (60 / $elapse_min)));

        if ($recipients instanceof Collection) {
            $recipients = $random
                ? $recipients->random(fn($items) => min($rate, $items->count()))
                : $recipients->take($rate);
        }

        if ($recipients instanceof Builder) {
            $recipients = $recipients->take($rate)->get();
        }

        $total = $recipients->count();

        $this->info("Sending to $total recipients...");

        $every = $total ? round($elapse_min * 60 / ($total + 1)) : 0;
        $scheduleFor = now();

        foreach($recipients as $recipient) {
            $scheduleFor->addSeconds($every);

            $newsletter->schedule($recipient, $scheduleFor->copy()->addSeconds(rand(0, 59)));
        }
    }
}
