<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\MailerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Runtime registrations are static; reset them between tests.
        Mailer::flushRegistrations();
    }

    /**
     * Register the package's service provider(s) in the test app.
     */
    protected function getPackageProviders($app)
    {
        return [
            MailerServiceProvider::class,
        ];
    }

    /**
     * Define the test environment (in-memory sqlite).
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
