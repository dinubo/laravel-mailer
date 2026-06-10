<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\MailerServiceProvider;
use Dinubo\Mailer\Placeholder;
use Illuminate\Support\ServiceProvider;

class MailerServiceProviderTest extends TestCase
{
    public function test_the_service_provider_is_registered(): void
    {
        $this->assertArrayHasKey(
            MailerServiceProvider::class,
            $this->app->getLoadedProviders()
        );
    }

    public function test_the_package_config_is_merged(): void
    {
        $config = config('mailer');

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    public function test_the_mailer_registrar_is_usable_statically(): void
    {
        Mailer::placeholders(Placeholder::make('greeting', 'hi'));

        $this->assertArrayHasKey('greeting', Mailer::registeredPlaceholders());
    }

    public function test_the_provider_advertises_no_deferred_services(): void
    {
        // The old provides() => ['mailer'] was dead: the provider isn't deferred,
        // and 'mailer' is the framework's mail manager, not this package's.
        $provider = new MailerServiceProvider($this->app);

        $this->assertFalse($provider->isDeferred());
        $this->assertSame([], $provider->provides());
    }

    public function test_the_facade_is_dropped_in_favour_of_static_calls(): void
    {
        // Mailer is an all-static registrar; the redundant facade + singleton
        // were removed (see STATUS Step 5).
        $this->assertFalse(class_exists('Dinubo\\Mailer\\Facades\\Mailer'));
        $this->assertFalse($this->app->bound('newsletter-mailer'));
    }

    public function test_migrations_publish_tag_is_registered(): void
    {
        $paths = ServiceProvider::pathsToPublish(MailerServiceProvider::class, 'mailer.migrations');

        $this->assertNotEmpty($paths, 'a mailer.migrations publish tag should be registered');
        $this->assertFileExists(array_key_first($paths));
    }
}
