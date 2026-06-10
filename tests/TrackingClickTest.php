<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Http\Controllers\TrackingClickController;
use Dinubo\Mailer\Models\Contact;
use Dinubo\Mailer\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrackingClickTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_keyless_click_redirects_and_records_without_error(): void
    {
        $message = $this->message();

        // Route is /click/{refId}/{key?} — no key must not pass null to toUuid(string).
        $response = (new TrackingClickController())($message->refId());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue($message->fresh()->logs()->where('action', 'click')->exists());
    }

    public function test_a_click_with_a_key_but_null_links_redirects_without_error(): void
    {
        $message = $this->message(); // links is null

        // key_exists() against a null `links` column must not crash.
        $response = (new TrackingClickController())($message->refId(), str_repeat('b', 32));

        $this->assertSame(302, $response->getStatusCode());
    }

    private function message(): Message
    {
        return Contact::from('user@example.com')->messages()->create([
            'category' => 'communication',
            'subject' => 'x',
        ]);
    }
}
