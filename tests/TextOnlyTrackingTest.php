<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class TextOnlyTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_text_only_email_with_tracking_enabled_does_not_crash(): void
    {
        config(['mail.default' => 'array']); // real transport so MessageSending/Sent fire

        // Force the tracking-enabled profile for plain transactional mail, so the open/
        // click-tracking injectors run against a message that has NO HTML body at all.
        config([
            'mailer.transactional.enable_open_tracking' => true,
            'mailer.transactional.enable_click_tracking' => true,
        ]);

        Mail::raw('Just text — no HTML body at all.', function ($message) {
            $message->from('from@example.com')
                ->to('to@example.com')
                ->subject('Hello');
        });

        // The listeners ran without a null-HTML-body TypeError and recorded the message.
        $this->assertSame(1, Message::count());
    }
}
