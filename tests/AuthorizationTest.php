<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Mailer;
use Illuminate\Http\Request;

class AuthorizationTest extends TestCase
{
    public function test_check_does_not_fatal_and_defaults_to_local_env_when_no_callback(): void
    {
        // No Mailer::auth() registered → check() must not throw; it defaults to the
        // local-env gate. Testbench boots the app as 'testing', not 'local', so false.
        $this->assertFalse(Mailer::check(Request::create('/')));
    }

    public function test_a_registered_auth_callback_drives_the_check(): void
    {
        Mailer::auth(fn () => true);
        $this->assertTrue(Mailer::check(Request::create('/')));

        Mailer::auth(fn () => false);
        $this->assertFalse(Mailer::check(Request::create('/')));
    }
}
