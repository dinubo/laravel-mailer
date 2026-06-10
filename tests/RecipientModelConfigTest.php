<?php

namespace Dinubo\Mailer\Tests;

class RecipientModelConfigTest extends TestCase
{
    public function test_recipient_model_defaults_to_the_auth_provider_model(): void
    {
        // The default must be derived from the host's user provider, not a
        // hardcoded \App\Models\User (which need not exist in a host app).
        $this->assertSame(
            config('auth.providers.users.model'),
            config('mailer.recipient_model'),
        );

        $this->assertNotSame('App\\Models\\User', config('mailer.recipient_model'));
    }
}
