<?php

namespace Dinubo\Mailer\Tests;

use Closure;

class ConfigCacheableTest extends TestCase
{
    public function test_config_contains_no_closures(): void
    {
        // Closures in config break `php artisan config:cache`. Dynamic
        // behaviour (placeholders/filters) lives in the Mailer registrar.
        $this->assertNoClosures(config('mailer'));
    }

    private function assertNoClosures($value): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->assertNoClosures($item);
            }

            return;
        }

        $this->assertNotInstanceOf(Closure::class, $value);
    }
}
