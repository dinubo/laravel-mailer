<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Mail\Newsletter as NewsletterMail;
use Dinubo\Mailer\Models\Newsletter;
use Dinubo\Mailer\Tests\Fixtures\Recipient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SendPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('test_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('name');
        });
    }

    public function test_sending_a_newsletter_runs_the_listeners_end_to_end(): void
    {
        // Real array transport (NOT Mail::fake) so MessageSending / MessageSent fire
        // and the package listeners run against a real Symfony Email.
        config(['mail.default' => 'array']);

        $recipient = Recipient::create([
            'email' => 'ada@example.com',
            'name' => 'Ada',
        ]);

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
