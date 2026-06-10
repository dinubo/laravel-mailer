<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Http\Controllers\NewsletterStatisticsController;
use Dinubo\Mailer\Models\Message;
use Dinubo\Mailer\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NewsletterStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function sentMessage(string $sentAt): void
    {
        $message = new Message();
        $message->contact_id = 1;
        $message->subject = 'x';
        $message->send_at = $sentAt;
        $message->save();
    }

    public function test_statistics_defaults_to_a_21_day_window(): void
    {
        $data = Newsletter::statistics();

        $this->assertCount(21, $data['labels']);
        $this->assertSame(today()->subDays(20)->toDateString(), $data['labels'][0]);
        $this->assertSame(today()->toDateString(), $data['labels'][20]);
    }

    public function test_statistics_honours_an_explicit_from_to_range(): void
    {
        $this->sentMessage(today()->subDays(3)->setTime(12, 0)->toDateTimeString());  // in range
        $this->sentMessage(today()->subDays(40)->setTime(12, 0)->toDateTimeString()); // out of range

        $from = today()->subDays(6)->toDateString();
        $to = today()->toDateString();

        $data = Newsletter::statistics(null, $from, $to);

        $this->assertCount(7, $data['labels']);
        $this->assertSame($from, $data['labels'][0]);
        $this->assertSame($to, $data['labels'][6]);

        // "Sent" is the first dataset; only the in-range message should be counted.
        $this->assertSame('Sent', $data['datasets'][0]['label']);
        $this->assertSame(1, array_sum($data['datasets'][0]['data']));
    }

    private function index(array $query)
    {
        return (new NewsletterStatisticsController())
            ->index(Request::create('/', 'GET', $query));
    }

    public function test_index_returns_chart_data_for_a_valid_range(): void
    {
        $response = $this->index([
            'from' => today()->subDays(6)->toDateString(),
            'to' => today()->toDateString(),
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(7, $response->getData(true)['labels']);
    }

    public function test_index_rejects_an_inverted_range(): void
    {
        $this->expectException(ValidationException::class);

        $this->index([
            'from' => today()->toDateString(),
            'to' => today()->subDays(5)->toDateString(),
        ]);
    }

    public function test_index_rejects_a_non_date(): void
    {
        $this->expectException(ValidationException::class);

        $this->index([
            'from' => 'not-a-date',
            'to' => today()->toDateString(),
        ]);
    }
}
