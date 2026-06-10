<?php

namespace Dinubo\Mailer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Dinubo\Mailer\Mail\Newsletter;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Message;

class SendNewsletter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Message $message;

    public ?string $event;

    public array $args;

    /**
     * Create a new job instance.
     *
     * The event is stored by id (a string), not as an Event object: the Event holds
     * closures (placeholders/sample), and serializing those onto a queue throws
     * "Serialization of 'Closure' is not allowed". It is re-resolved from the
     * registrar in handle().
     *
     * @param array<string, mixed> $args
     */
    public function __construct(Message $message, ?string $event = null, array $args = [])
    {
        $this->message = $message;
        $this->event = $event;
        $this->args = $args;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->message->receivable || $this->message->receivable->trashed()) {
            $this->message->drop();

            return;
        }

        if (!$this->message->contact->allowed()) {
            $this->message->drop();

            return;
        }

        if ($this->message->logs()->where('action', 'send')->exists()) {
            $this->message->drop();

            return;
        }

        $event = $this->event ? (Mailer::registeredEvents()[$this->event] ?? null) : null;

        $placeholders = $event
            ? $event->buildPlaceholders($this->message->receivable, $this->args)
            : [];

        $placeholders = array_merge($placeholders, $this->message->mailable->processAction($this->message->receivable));

        Mail::send(new Newsletter($this->message, $placeholders));
    }
}
