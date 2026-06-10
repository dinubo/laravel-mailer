<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Segment;

class SegmentRegistryTest extends TestCase
{
    public function test_an_all_users_segment_is_available_by_default(): void
    {
        // No segments registered (setUp flushes) — the built-in default must still exist.
        $segments = Mailer::registeredSegments();

        $this->assertArrayHasKey('all', $segments);
        $this->assertSame('All Users', $segments['all']->name);

        // ...and is surfaced to the admin UI / validation list.
        $this->assertSame(
            ['value' => 'all', 'name' => 'All Users'],
            Mailer::getSegments()->get('all'),
        );
    }

    public function test_registered_segments_augment_and_can_override_the_default(): void
    {
        Mailer::segments([
            Segment::make('verified', 'Verified Users'),
            Segment::make('all', 'Everyone'), // overrides the built-in default
        ]);

        $segments = Mailer::registeredSegments();

        $this->assertArrayHasKey('verified', $segments);
        $this->assertSame('Verified Users', $segments['verified']->name);
        $this->assertSame('Everyone', $segments['all']->name);
    }
}
