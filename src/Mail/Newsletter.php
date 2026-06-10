<?php

namespace Dinubo\Mailer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Dinubo\Mailer\Models\Message;
use Dinubo\Mailer\Traits\Communication;

class Newsletter extends Mailable
{
    use Queueable, SerializesModels;
    use Communication;

    public Message $message;

    public array $placeholders;

    /**
     * @param array<string, string> $placeholders
     */
    public function __construct(Message $message, array $placeholders = [])
    {
        $this->message = $message;
        $this->placeholders = $placeholders;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->substitute($this->message->mailable->subject),
            to: [
                new Address($this->message->receivable->email, $this->message->receivable->name),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // `?[label](url)` is shorthand for a link; expand it to a standard Markdown
        // link. (The previous Blade `mail::button` shortcut is dropped for v0.1.0.)
        $body = preg_replace('/\?\[(.*?)\]\((.*?)\)/', '[$1]($2)', $this->message->mailable->body);

        return new Content(
            markdown: 'mailer::newsletter',
            with: [
                'body' => $this->substitute($body),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Ref-ID' => $this->message->refId(),
            ],
        );
    }

    /**
     * Replace `{{placeholder}}` tokens by plain string substitution.
     *
     * Deliberately NOT Blade: subject/body are user-authored content and must never
     * be compiled or evaluated as templates (no server-side template injection).
     */
    protected function substitute(string $text): string
    {
        $placeholders = array_merge(
            $this->message->mailable->buildPlaceholders($this->message->receivable),
            $this->placeholders,
        );

        $keys = collect($placeholders)->map(fn ($value, $key) => '{{' . $key . '}}')->all();

        return str_replace($keys, $placeholders, $text);
    }
}
