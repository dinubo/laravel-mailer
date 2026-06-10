<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Filter;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Newsletter;
use Dinubo\Mailer\Placeholder;
use Dinubo\Mailer\Segment;
use Illuminate\Database\Eloquent\Model;

class RegistrarTest extends TestCase
{
    public function test_placeholders_can_be_registered_and_listed_for_the_ui(): void
    {
        Mailer::placeholders([
            Placeholder::make('name', fn (Model $recipient) => $recipient->name),
        ]);

        $listed = Mailer::getPlaceholders();

        $this->assertSame(
            ['key' => '{{name}}', 'name' => 'name'],
            $listed->get('name'),
        );
    }

    public function test_registered_placeholders_are_resolved_against_a_recipient(): void
    {
        Mailer::placeholders([
            Placeholder::make('name', fn (Model $recipient) => $recipient->name),
            Placeholder::make('site', 'Acme'),
        ]);

        $recipient = new class extends Model {
            protected $guarded = [];
        };
        $recipient->name = 'Ada';

        $resolved = (new Newsletter)->buildPlaceholders($recipient);

        $this->assertSame(['name' => 'Ada', 'site' => 'Acme'], $resolved);
    }

    public function test_a_global_filter_can_be_registered(): void
    {
        $query = fn ($q, $n) => $q;
        $collection = fn ($c, $n) => $c;

        $this->assertNull(Mailer::registeredFilter());

        Mailer::filter(Filter::make(query: $query, collection: $collection));

        $filter = Mailer::registeredFilter();

        $this->assertInstanceOf(Filter::class, $filter);
        $this->assertSame($query, $filter->query);
        $this->assertSame($collection, $filter->collection);
    }

    public function test_segments_can_be_registered_with_a_filter(): void
    {
        $query = fn ($q, $n) => $q;

        Mailer::segments(
            Segment::make('all', 'All Users')->filter(Filter::make(query: $query)),
        );

        $segments = Mailer::registeredSegments();

        $this->assertArrayHasKey('all', $segments);
        $this->assertSame('All Users', $segments['all']->name);
        $this->assertSame($query, $segments['all']->filter->query);
        $this->assertNull($segments['all']->filter->collection);
    }
}
