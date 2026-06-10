<?php

use Dinubo\Mailer\Http\Middleware\Authorize;

return [

    'bounce_address' => env('BOUNCE_ADDRESS'),

    'unsubscribe_address' => env('UNSUBSCRIBE_ADDRESS'),

    // Inbound provider webhook secrets. When set, the matching callback route verifies
    // the request and rejects mismatches (401); when empty, the request is allowed
    // through with a logged warning. See the VerifyPostmark/ResendSignature middleware.
    'postmark' => [
        'webhook_secret' => env('MAILER_POSTMARK_WEBHOOK_SECRET'),
    ],

    'resend' => [
        'webhook_secret' => env('MAILER_RESEND_WEBHOOK_SECRET'),
    ],

    'transactional' => [

        'enforce_unsubscriptions' => false,

        'enable_click_tracking' => false,

        'enable_open_tracking' => false,

    ],

    'communication' => [

        'enforce_unsubscriptions' => true,

        'enable_click_tracking' => true,

        'enable_open_tracking' => true,

    ],

    'middleware' => [

        'user' => [
            'web',
        ],

        'admin' => [
            'web',
            // 'auth:panel',
            Authorize::class,
        ],

        // Applied to inbound provider webhooks (Postmark/Resend). Left empty so
        // external POSTs aren't blocked by CSRF; add signature verification here.
        'callback' => [
            //
        ],

    ],

    'mailables' => [
        // 'newsletter',
    ],

    'receivables' => [
        // 'user',
    ],

    'recipient_model' => config('auth.providers.users.model'),

    /*
    |--------------------------------------------------------------------------
    | Placeholders, filters & segments
    |--------------------------------------------------------------------------
    |
    | Placeholders and filters use closures, so they live in the Mailer registrar
    | rather than in config (config must stay cacheable via `php artisan
    | config:cache`). Register them from a service provider's boot() method:
    |
    |   use Dinubo\Mailer\Mailer;
    |   use Dinubo\Mailer\Placeholder;
    |   use Dinubo\Mailer\Filter;
    |   use Dinubo\Mailer\Segment;
    |   use Illuminate\Database\Eloquent\Builder;
    |   use Illuminate\Database\Eloquent\Model;
    |
    |   Mailer::placeholders([
    |       Placeholder::make('name', fn (Model $recipient) => $recipient->name),
    |   ]);
    |
    |   Mailer::filter(Filter::make(
    |       query: fn (Builder $query, $newsletter) => $query->whereNotNull('email_verified_at'),
    |   ));
    |
    |   // A built-in "all" (All Users) segment is always available; add more:
    |   Mailer::segments([
    |       Segment::make('verified', 'Verified users')->filter(Filter::make(
    |           query: fn (Builder $query, $newsletter) => $query->whereNotNull('email_verified_at'),
    |       )),
    |   ]);
    |
    */

];
