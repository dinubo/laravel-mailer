<?php

namespace Dinubo\Mailer;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Dinubo\Mailer\Models\Newsletter;
use Illuminate\Http\Request;

class Mailer
{
    /**
     * Registered placeholders, keyed by name.
     *
     * @var array<string, Placeholder>
     */
    protected static array $placeholders = [];

    protected static ?Filter $filter = null;

    /**
     * Registered segments, keyed by name.
     *
     * @var array<string, Segment>
     */
    protected static array $segments = [];

    /**
     * Registered events, keyed by name.
     *
     * @var array<string, Event>
     */
    protected static array $events = [];

    /**
     * Registered actions, keyed by name.
     *
     * @var array<string, Action>
     */
    protected static array $actions = [];

    /**
     *
     * @param array<int, Placeholder>|Placeholder $placeholders
     */
    public static function placeholders(array|Placeholder $placeholders): void
    {
        $placeholders = is_array($placeholders) ? $placeholders : [$placeholders];

        foreach ($placeholders as $placeholder) {
            static::$placeholders[$placeholder->id] = $placeholder;
        }
    }

    /**
     * The raw registered placeholders.
     *
     * @return array<string, \Dinubo\Mailer\Placeholder>
     */
    public static function registeredPlaceholders(): array
    {
        return static::$placeholders;
    }

    public static function filter(?Filter $filter): void
    {
        static::$filter = $filter;
    }

    public static function registeredFilter(): ?Filter
    {
        return static::$filter;
    }

    /**
     *
     * @param array<int, Segment>|Segment $segments
     */
    public static function segments(array|Segment $segments): void
    {
        $segments = is_array($segments) ? $segments : [$segments];

        foreach ($segments as $segment) {
            static::$segments[$segment->id] = $segment;
        }
    }

    /**
     *
     * @return array<string, \Dinubo\Mailer\Segment>
     */
    public static function registeredSegments(): array
    {
        // A built-in "All Users" (no filter) segment is always available; a host can
        // override it or add more via Mailer::segments(...).
        return array_merge(
            ['all' => Segment::make('all', 'All Users')],
            static::$segments,
        );
    }

    /**
     *
     * @param array<int, Event>|Event $events
     */
    public static function events(array|Event $events): void
    {
        $events = is_array($events) ? $events : [$events];

        foreach ($events as $event) {
            static::$events[$event->id] = $event;
        }
    }

    /**
     *
     * @return array<string, \Dinubo\Mailer\Event>
     */
    public static function registeredEvents(): array
    {
        return static::$events;
    }

    /**
     *
     * @param array<int, Action>|Action $actions
     */
    public static function actions(array|Action $actions): void
    {
        $actions = is_array($actions) ? $actions : [$actions];

        foreach ($actions as $action) {
            static::$actions[$action->id] = $action;
        }
    }

    /**
     *
     * @return array<string, \Dinubo\Mailer\Action>
     */
    public static function registeredActions(): array
    {
        return static::$actions;
    }

    /**
     * Clear all runtime registrations (primarily for tests).
     */
    public static function flushRegistrations(): void
    {
        static::$placeholders = [];
        static::$segments = [];
        static::$events = [];
        static::$actions = [];
        static::$filter = null;
        static::$authUsing = null;
    }

    public static function toUuid(string $refId): string
    {
        if (is_string($refId) && strlen($refId) === 32) {
            return substr($refId, 0, 8) . '-'
                . substr($refId, 8, 4) . '-'
                . substr($refId, 12, 4) . '-'
                . substr($refId, 16, 4) . '-'
                . substr($refId, 20);
        }

        return $refId;
    }

    public static function toPlainId(string $uuid): string
    {
        return str_replace('-', '', $uuid);
    }

    public static function event(string $id, Model $recipient, mixed ...$args): void
    {
        $event = static::$events[$id] ?? null;

        if (! $event) {
            return;
        }

        $event->process($recipient, $args);
    }

    public static function action(string $id, Model $recipient, Newsletter $newsletter): array
    {
        $action = static::$actions[$id] ?? null;

        if (! $action) {
            return [];
        }

        return $action->process($recipient, $newsletter);
    }

    public static function getPlaceholders(): Collection
    {
        return collect(static::$placeholders)
            ->map(fn($value, $key) => [
                'key' => '{{' . $key . '}}',
                'name' => $key
            ]);
    }

    public static function getSegments(): Collection
    {
        return collect(static::registeredSegments())
            ->map(function($segment, $key) {
                return [
                    'value' => $key,
                    'name' => $segment->name
                ];
            });
    }

    public static function getEvents(): Collection
    {
        return collect(static::$events)
            ->map(function($event, $key) {
                return [
                    'value' => $key,
                    'name' => $event->name,
                    'placeholders' => collect($event->placeholders)->map(fn($value, $key) => [
                        'key' => '{{' . $key . '}}',
                        'name' => Str::headline($key)
                    ]),
                ];
            });
    }

    public static function getActions(): Collection
    {
        return collect(static::$actions)
            ->map(function($action, $key) {
                return [
                    'value' => $key,
                    'name' => $action->name,
                    'placeholders' => collect($action->placeholders)->map(fn($value, $key) => [
                        'key' => '{{' . $key . '}}',
                        'name' => Str::headline($key)
                    ]),
                ];
            });
    }

    public static ?Closure $authUsing = null;

    public static function auth(Closure $callback): static
    {
        static::$authUsing = $callback;

        return new static;
    }

    public static function check(Request $request): bool
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }
}
