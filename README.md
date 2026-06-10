# Mailer

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Tests][ico-tests]][link-tests]
[![Total Downloads][ico-downloads]][link-downloads]

A Laravel newsletter / mailer package: schedule and send segmented newsletters,
resolve per-recipient placeholders, track opens and clicks, handle unsubscribes,
and process provider webhooks (Postmark, Resend) — with a built-in admin UI.

## Installation

Via Composer:

``` bash
composer require dinubo/laravel-mailer
```

The service provider and the package's migrations are auto-discovered. Run them:

``` bash
php artisan migrate
```

Publish whatever you want to customise:

``` bash
php artisan vendor:publish --tag=mailer.config      # config/mailer.php
php artisan vendor:publish --tag=mailer.views       # admin Blade views
php artisan vendor:publish --tag=mailer.migrations  # only if you need to edit the schema
```

## Usage

### Routes

All routes are mounted under the `/mailer` prefix and named with a `mailer.`
prefix. The admin UI lives at `/mailer/newsletters`. Middleware is configurable
per group (`user`, `admin`, `callback`) in `config/mailer.php`.

| Group    | Examples                                                      |
| -------- | ------------------------------------------------------------- |
| user     | `mailer.open`, `mailer.click`, `mailer.unsubscribe`          |
| admin    | `mailer.newsletters.*`, `mailer.newsletters.statistics.*`    |
| callback | `mailer.callback.postmark`, `mailer.callback.resend`        |

The callback (webhook) routes verify the provider signature when the matching
secret is configured (see [Webhooks](#webhooks)); the `mailer.middleware.callback`
group is available for any additional middleware.

### Recipients

By default newsletters are sent to your application's configured user provider
model (`config('auth.providers.users.model')`). Override it in `config/mailer.php`
via `recipient_model`.

### Placeholders, filters & segments (registrar)

Placeholders and filters use closures, so they are registered on the `Mailer`
class rather than stored in config (this keeps config cacheable). Register them
from a service provider's `boot()` method:

``` php
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Placeholder;
use Dinubo\Mailer\Filter;
use Dinubo\Mailer\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Available as {{name}} in a newsletter subject/body (plain substitution, not Blade).
Mailer::placeholders([
    Placeholder::make('name', fn (Model $recipient) => $recipient->name),
    Placeholder::make('site', 'Acme Inc.'),
]);

// Global recipient filter, applied to every send.
Mailer::filter(Filter::make(
    query: fn (Builder $query, $newsletter) => $query->whereNotNull('email_verified_at'),
));

// Segments power the admin dropdown. A built-in "all" (All Users, no filter) is
// always available; register more, each with an optional filter:
Mailer::segments([
    Segment::make('verified', 'Verified users')->filter(Filter::make(
        query: fn (Builder $query, $newsletter) => $query->whereNotNull('email_verified_at'),
    )),
]);
```

A `Filter` may define a `query:` closure (constrains the recipient query) and/or a
`collection:` closure (post-filters the resulting collection).

### Events & actions

Trigger event-scheduled newsletters from your app, and compute extra
per-recipient placeholders at send time:

``` php
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Event;
use Dinubo\Mailer\Placeholder;
use Illuminate\Database\Eloquent\Model;

Mailer::events([
    Event::make('signup', 'On signup')
        ->placeholders([Placeholder::make('plan', fn (Model $user) => $user->plan)]),
]);

// Elsewhere in your app — schedules any active newsletters bound to this event:
Mailer::event('signup', $user);
```

`Action`s work similarly (`Action::make(...)->execute(...)`, registered via
`Mailer::actions([...])`) for placeholders that run logic per recipient.

### Admin access

The admin UI (`/mailer/newsletters`) is gated by the `admin` middleware group,
which by default only allows the `local` environment. Authorize real users with
`Mailer::auth()`:

``` php
use Dinubo\Mailer\Mailer;
use Illuminate\Http\Request;

Mailer::auth(fn (Request $request) => (bool) $request->user()?->is_admin);
```

### Webhooks

Bounce/complaint webhooks from Postmark and Resend update delivery state. Set the
matching secret to enable signature verification (without it, requests are
accepted and a warning is logged):

``` dotenv
# Sent by Postmark as HTTP Basic Auth — set the webhook URL to
# https://user:SECRET@your-app.test/mailer/callback/postmark
MAILER_POSTMARK_WEBHOOK_SECRET=...

# Resend (Svix) signing secret
MAILER_RESEND_WEBHOOK_SECRET=whsec_...
```

### Sending

Newsletters scheduled in the admin UI are dispatched by the scheduler command.
Add it to your console schedule:

``` php
$schedule->command('mailer:newsletters')->everyFifteenMinutes();
```

## Change log

Please see the [changelog](CHANGELOG.md) for more information on what has changed recently.

## Testing

The package uses [orchestra/testbench](https://github.com/orchestral/testbench):

``` bash
composer install
vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email petros@dinubo.com instead of using the issue tracker.

## Credits

- [Dinubo][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/dinubo/laravel-mailer.svg?style=flat-square
[ico-tests]: https://img.shields.io/github/actions/workflow/status/dinubo/laravel-mailer/tests.yml?branch=main&label=tests&style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/dinubo/laravel-mailer.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/dinubo/laravel-mailer
[link-tests]: https://github.com/dinubo/laravel-mailer/actions/workflows/tests.yml
[link-downloads]: https://packagist.org/packages/dinubo/laravel-mailer
[link-author]: https://github.com/dinubo
[link-contributors]: ../../contributors
