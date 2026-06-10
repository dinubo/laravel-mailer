<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Mail\Newsletter as NewsletterMail;
use Dinubo\Mailer\Models\Newsletter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class SendPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_a_newsletter_runs_the_listeners_end_to_end(): void
    {
        // Real array transport (NOT Mail::fake) so MessageSending / MessageSent fire
        // and the package listeners run against a real Symfony Email.
        config(['mail.default' => 'array']);

        $recipient = new class extends Model {
            protected $guarded = [];
        };
        $recipient->email = 'ada@example.com';
        $recipient->name = 'Ada';

        $newsletter = Newsletter::create([
            'subject' => 'Hi {{name}}',
            'body' => 'Hello {{name}} — visit [our promo](https://example.com/promo)',
            'category' => 'communication', // selects the tracking-enabled profile
            'after_sec' => 0,              // column is NOT NULL with no default
        ]);

        $message = $newsletter->message($recipient);
        $message->save();

        Mail::send(new NewsletterMail($message, ['name' => 'Ada']));

        $message->refresh();

        // ProcessSentMessage (MessageSent) recorded the send.
        $this->assertNotNull($message->send_at, 'send_at should be stamped by the sent listener');
        $this->assertTrue($message->logs()->where('action', 'send')->exists());

        // ProcessOutgoingMessage (MessageSending) rendered + stored the email and captured
        // the link for click tracking — proving the whole Symfony-Email pipeline ran with
        // no Swift_Message errors.
        $this->assertNotNull($message->body, 'the rendered email should be stored on the message');
        $this->assertContains(
            'https://example.com/promo',
            array_values($message->links ?? []),
            'click tracking should capture the body link',
        );
    }
}
