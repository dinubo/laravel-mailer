<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Action;
use Dinubo\Mailer\Models\Newsletter;
use Dinubo\Mailer\Placeholder;
use Illuminate\Database\Eloquent\Model;

class ValueObjectTest extends TestCase
{
    public function test_placeholder_build_casts_a_non_string_closure_result_to_string(): void
    {
        $recipient = new class extends Model {
            protected $guarded = [];
        };

        // Closures may legitimately return non-strings; build() must not TypeError.
        $this->assertSame('42', Placeholder::make('credits', fn () => 42)->build($recipient));
        $this->assertSame('', Placeholder::make('maybe', fn () => null)->build($recipient));
    }

    public function test_action_process_returns_empty_when_no_execute_closure_is_set(): void
    {
        $recipient = new class extends Model {
            protected $guarded = [];
        };

        // No execute() configured — must return [] instead of calling a null callable.
        $this->assertSame([], Action::make('noop', 'No-op')->process($recipient, new Newsletter()));
    }
}
