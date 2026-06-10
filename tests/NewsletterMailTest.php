<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Mail\Newsletter as NewsletterMail;
use Dinubo\Mailer\Models\Message;
use Dinubo\Mailer\Models\Newsletter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;

class NewsletterMailTest extends TestCase
{
    public function test_the_mailable_is_not_self_queued(): void
    {
        // ShouldQueue belongs on the SendNewsletter job, not the mailable, so the
        // job's duplicate-send guard and the real send stay in a single process.
        $this->assertNotInstanceOf(ShouldQueue::class, new NewsletterMail(new Message()));
    }

    public function test_it_substitutes_placeholders_without_evaluating_them_as_templates(): void
    {
        $mailable = $this->makeMailable(
            subject: 'Hi {{name}}',
            body: 'Hello {{name}} — ?[Click here](https://example.com/x) — {{ 2+2 }}',
            placeholders: ['name' => 'Ada'],
        );

        $html = $mailable->render();

        // {{name}} substituted with the placeholder value
        $this->assertStringContainsString('Ada', $html);
        // ?[label](url) became a real Markdown link
        $this->assertStringContainsString('https://example.com/x', $html);
        $this->assertStringContainsString('Click here', $html);
        // no Blade/template evaluation: {{ 2+2 }} must stay literal, never become "4"
        $this->assertStringContainsString('2+2', $html);
    }

    private function makeMailable(string $subject, string $body, array $placeholders): NewsletterMail
    {
        $newsletter = new Newsletter(['subject' => $subject, 'body' => $body]);

        $recipient = new class extends Model {
            protected $guarded = [];
        };
        $recipient->email = 'ada@example.com';
        $recipient->name = 'Ada';

        $message = new Message();
        $message->uuid = str_repeat('a', 32); // refId() derives from the uuid
        $message->setRelation('mailable', $newsletter);
        $message->setRelation('receivable', $recipient);

        return new NewsletterMail($message, $placeholders);
    }
}
