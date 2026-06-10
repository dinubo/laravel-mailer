<?php

namespace Dinubo\Mailer\Tests;

use Illuminate\Support\Facades\Route;

class RoutesTest extends TestCase
{
    public function test_user_facing_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('mailer.open'));
        $this->assertTrue(Route::has('mailer.click'));
        $this->assertTrue(Route::has('mailer.unsubscribe'));
    }

    public function test_admin_routes_are_registered_under_the_mailer_prefix(): void
    {
        $this->assertTrue(Route::has('mailer.newsletters.index'));
        $this->assertTrue(Route::has('mailer.newsletters.statistics.index'));

        // Old unprefixed names must no longer exist.
        $this->assertFalse(Route::has('newsletters.index'));
    }

    public function test_callback_routes_are_named(): void
    {
        $this->assertTrue(Route::has('mailer.callback.postmark'));
        $this->assertTrue(Route::has('mailer.callback.resend'));
    }

    public function test_the_newsletter_send_route_is_post_not_get(): void
    {
        // Sending is destructive (persists a Message + dispatches mail), so it must not
        // be reachable via a GET link (prefetch/crawler/CSRF). It is a CSRF-guarded POST.
        $route = Route::getRoutes()->getByName('mailer.newsletters.send');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertNotContains('GET', $route->methods());
    }
}
