<?php

namespace Dinubo\Mailer\Tests;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

class AdminViewsTest extends TestCase
{
    public function test_package_ships_its_own_layout_and_errors_partial(): void
    {
        $this->assertTrue(View::exists('mailer::layouts.app'), 'mailer::layouts.app should be shipped by the package');
        $this->assertTrue(View::exists('mailer::errors._errors'), 'mailer::errors._errors should be shipped by the package');
    }

    public function test_admin_views_do_not_reach_into_host_app_views(): void
    {
        $views = glob(__DIR__.'/../resources/views/newsletters/*.blade.php');

        $this->assertNotEmpty($views);

        foreach ($views as $view) {
            $contents = file_get_contents($view);

            $this->assertStringNotContainsString("@extends('layouts.app')", $contents, basename($view).' must not extend the host layout');
            $this->assertStringNotContainsString("@include('errors.", $contents, basename($view).' must not include host error partials');
        }
    }

    public function test_errors_partial_renders_without_errors(): void
    {
        // The web middleware stack shares an (empty) ViewErrorBag on every request.
        View::share('errors', new ViewErrorBag);

        $html = View::make('mailer::errors._errors')->render();

        $this->assertSame('', trim($html));
    }
}
