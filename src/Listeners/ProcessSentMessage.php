<?php

namespace Dinubo\Mailer\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Message;
use Symfony\Component\Mime\Email;

class ProcessSentMessage
{
    private Email $mail;

    public function handle(MessageSent $event)
    {
        $this->mail = $event->message;

        // This fires for ALL mail in the host app, not just package mail — bail unless
        // it carries our ref id and matches a known message.
        if (! $this->mail->getHeaders()->has('X-Ref-ID')) {
            return;
        }

        $refId = $this->mail->getHeaders()->get('X-Ref-ID')->getBodyAsString();

        $message = Message::where('uuid', Mailer::toUuid($refId))->first();

        if (! $message) {
            return;
        }

        $messageId = null;

        if (property_exists($event, 'sent')) {
            $messageId = $event->sent->getSymfonySentMessage()->getMessageId();
        }

        if ($messageId === null) {
            $header = $this->mail->getHeaders()->get('X-Message-Id') ?? $this->mail->getHeaders()->get('X-PM-Message-Id');

            if ($header) {
                $messageId = $header->getBodyAsString();
            }
        }

        if ($messageId) {
            $message->update(['message_id' => $messageId]);
        }

        $message->sent();
    }
}
