<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Listeners\ProcessSentMessage;
use Dinubo\Mailer\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage as IlluminateSentMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class ProcessSentMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_no_ops_when_the_email_has_no_ref_id_header(): void
    {
        // The listener fires for ALL host-app mail; mail without our X-Ref-ID must be
        // ignored, not crash on a null header.
        (new ProcessSentMessage())->handle($this->messageSent($this->email()));

        $this->assertSame(0, Message::count());
    }

    public function test_it_no_ops_when_no_message_matches_the_ref_id(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader('X-Ref-ID', str_repeat('a', 32));

        // No matching Message row exists — must not dereference null.
        (new ProcessSentMessage())->handle($this->messageSent($email));

        $this->assertSame(0, Message::count());
    }

    private function email(): Email
    {
        return (new Email())
            ->from('from@example.com')
            ->to('to@example.com')
            ->subject('x')
            ->html('<p>hi</p>');
    }

    private function messageSent(Email $email): MessageSent
    {
        $symfonySent = new SymfonySentMessage(
            $email,
            new Envelope(new Address('from@example.com'), [new Address('to@example.com')]),
        );

        return new MessageSent(new IlluminateSentMessage($symfonySent));
    }
}
