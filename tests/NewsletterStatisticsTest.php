<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Http\Controllers\NewsletterStatisticsController;
use Dinubo\Mailer\Models\Message;
use Dinubo\Mailer\Models\Newsletter;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NewsletterStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function newsletter(): Newsletter
    {
        return Newsletter::create([
            'category' => 'x',
            'after_sec' => 0,
            'subject' => 's',
            'body' => 'b',
        ]);
    }

    private function message(array $attributes): void
    {
        $message = new Message();
        $message->contact_id = 1;
        $message->subject = 'x';

        foreach ($attributes as $key => $value) {
            $message->{$key} = $value;
        }

        $message->save();
    }

    public function test_statistics_defaults_to_a_21_day_window(): void
    {
        $data = Newsletter::statistics();

        $this->assertCount(21, $data['labels']);
        $this->assertSame(today()->subDays(20)->toDateString(), $data['labels'][0]);
        $this->assertSame(today()->toDateString(), $data['labels'][20]);
    }

    public function test_statistics_with_only_a_start_spans_the_window_forward(): void
    {
        $data = Newsletter::statistics(null, '2026-06-01', null);

        $this->assertCount(21, $data['labels']);
        $this->assertSame('2026-06-01', $data['labels'][0]);
        $this->assertSame('2026-06-21', $data['labels'][20]);
    }

    public function test_statistics_with_only_an_end_spans_the_window_back(): void
    {
        $data = Newsletter::statistics(null, null, '2026-06-30');

        $this->assertCount(21, $data['labels']);
        $this->assertSame('2026-06-10', $data['labels'][0]);
        $this->assertSame('2026-06-30', $data['labels'][20]);
    }

    public function test_statistics_groups_counts_by_newsletter_and_buckets_the_rest(): void
    {
        $a = $this->newsletter();

        // Newsletter A: two sends + one click, all inside the default window.
        $this->message([
            'mailable_type' => Newsletter::class,
            'mailable_id' => $a->id,
            'send_at' => today()->subDays(3)->setTime(12, 0),
            'click_at' => today()->subDays(3)->setTime(13, 0),
        ]);
        $this->message([
            'mailable_type' => Newsletter::class,
            'mailable_id' => $a->id,
            'send_at' => today()->setTime(9, 0),
        ]);

        // A null-mailable message folds into the shared "other" series.
        $this->message(['send_at' => today()->setTime(10, 0)]);

        $data = Newsletter::statistics();
        $series = $data['series'];
        $key = (string) $a->id;

        $this->assertArrayHasKey($key, $series);
        $this->assertArrayHasKey('other', $series);

        // The per-newsletter column math: Sent / Clicked totals over the window.
        $this->assertSame(2, array_sum($series[$key]['send']));
        $this->assertSame(1, array_sum($series[$key]['click']));
        $this->assertSame(1, array_sum($series['other']['send']));

        // Per-day alignment: A sent once on (today - 3) and once today.
        $pastIndex = array_search(today()->subDays(3)->toDateString(), $data['labels'], true);
        $todayIndex = array_search(today()->toDateString(), $data['labels'], true);
        $this->assertSame(1, $series[$key]['send'][$pastIndex]);
        $this->assertSame(1, $series[$key]['send'][$todayIndex]);
    }

    public function test_statistics_buckets_by_the_registered_morph_alias(): void
    {
        Relation::morphMap(['newsletter' => Newsletter::class]);

        try {
            $a = $this->newsletter();

            $message = new Message();
            $message->contact_id = 1;
            $message->subject = 'x';
            $message->send_at = today()->setTime(9, 0);
            $message->mailable()->associate($a); // stores the 'newsletter' alias in mailable_type
            $message->save();

            $series = Newsletter::statistics()['series'];

            $this->assertArrayHasKey((string) $a->id, $series);
            $this->assertSame(1, array_sum($series[(string) $a->id]['send']));
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function test_statistics_honours_an_explicit_from_to_range(): void
    {
        $a = $this->newsletter();

        $this->message([
            'mailable_type' => Newsletter::class,
            'mailable_id' => $a->id,
            'send_at' => today()->subDays(3)->setTime(12, 0), // in range
        ]);
        $this->message([
            'mailable_type' => Newsletter::class,
            'mailable_id' => $a->id,
            'send_at' => today()->subDays(40)->setTime(12, 0), // out of range
        ]);

        $from = today()->subDays(6)->toDateString();
        $to = today()->toDateString();

        $data = Newsletter::statistics(null, $from, $to);

        $this->assertCount(7, $data['labels']);
        $this->assertSame($from, $data['labels'][0]);
        $this->assertSame($to, $data['labels'][6]);
        $this->assertSame(1, array_sum($data['series'][(string) $a->id]['send']));
    }

    public function test_get_statistics_scopes_to_the_single_newsletter(): void
    {
        $a = $this->newsletter();
        $b = $this->newsletter();

        $this->message(['mailable_type' => Newsletter::class, 'mailable_id' => $a->id, 'send_at' => today()->setTime(9, 0)]);
        $this->message(['mailable_type' => Newsletter::class, 'mailable_id' => $b->id, 'send_at' => today()->setTime(9, 0)]);

        $series = $a->getStatistics()['series'];

        $this->assertArrayHasKey((string) $a->id, $series);
        $this->assertArrayNotHasKey((string) $b->id, $series);
        $this->assertSame(1, array_sum($series[(string) $a->id]['send']));
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
