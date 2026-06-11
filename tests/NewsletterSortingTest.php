<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Http\Controllers\NewsletterController;
use Dinubo\Mailer\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NewsletterSortingTest extends TestCase
{
    use RefreshDatabase;

    private function newsletter(array $attributes): Newsletter
    {
        return Newsletter::create(array_merge([
            'category' => 'general',
            'after_sec' => 0,
            'subject' => 'subject',
            'body' => 'body',
        ], $attributes));
    }

    /** @return array<int> the ordered newsletter ids the index controller would render */
    private function indexIds(array $query): array
    {
        $view = (new NewsletterController())->index(Request::create('/', 'GET', $query));

        return $view->getData()['newsletters']->pluck('id')->all();
    }

    public function test_sorts_by_a_whitelisted_column_in_both_directions(): void
    {
        $a = $this->newsletter(['subject' => 'Apple']);
        $b = $this->newsletter(['subject' => 'Banana']);
        $c = $this->newsletter(['subject' => 'Cherry']);

        $this->assertSame([$a->id, $b->id, $c->id], $this->indexIds(['sort' => 'subject', 'dir' => 'asc']));
        $this->assertSame([$c->id, $b->id, $a->id], $this->indexIds(['sort' => 'subject', 'dir' => 'desc']));
    }

    public function test_scheduled_sort_maps_to_the_after_sec_column(): void
    {
        $late = $this->newsletter(['after_sec' => 100]);
        $early = $this->newsletter(['after_sec' => 10]);

        $this->assertSame([$early->id, $late->id], $this->indexIds(['sort' => 'scheduled', 'dir' => 'asc']));
    }

    public function test_rejects_an_unknown_sort(): void
    {
        $this->expectException(ValidationException::class);

        $this->indexIds(['sort' => 'bogus']);
    }

    public function test_rejects_an_unknown_direction(): void
    {
        $this->expectException(ValidationException::class);

        $this->indexIds(['dir' => 'sideways']);
    }

    public function test_invalid_session_sort_falls_back_to_the_default(): void
    {
        $zebra = $this->newsletter(['category' => 'zebra']);
        $apple = $this->newsletter(['category' => 'apple']);

        session(['mailer.sort' => 'bogus']);

        // No sort on the request; the stale session value is ignored => default (category asc).
        $this->assertSame([$apple->id, $zebra->id], $this->indexIds([]));
    }

    public function test_sort_and_direction_persist_in_the_session(): void
    {
        $a = $this->newsletter(['subject' => 'Apple']);
        $b = $this->newsletter(['subject' => 'Banana']);

        // A request that sets sort/dir via the URL...
        $this->indexIds(['sort' => 'subject', 'dir' => 'desc']);

        // ...is remembered, so a later request without them reuses the session.
        $this->assertSame([$b->id, $a->id], $this->indexIds([]));
    }

    public function test_index_validates_the_date_range(): void
    {
        $this->expectException(ValidationException::class);

        (new NewsletterController())->index(Request::create('/', 'GET', [
            'from' => '2026-06-10',
            'to' => '2026-06-01',
        ]));
    }
}
