<?php

namespace Dinubo\Mailer\Traits;

trait HasEvents
{
    protected function initializeHasEvents()
    {
        $this->observables[] = 'opened';
        $this->observables[] = 'clicked';
        $this->observables[] = 'unsubscribed';
        $this->observables[] = 'bounced';
        $this->observables[] = 'spamReported';
    }

    public static function opened($callback)
    {
        static::registerModelEvent('opened', $callback);
    }

    public static function clicked($callback)
    {
        static::registerModelEvent('clicked', $callback);
    }

    public static function unsubscribed($callback)
    {
        static::registerModelEvent('unsubscribed', $callback);
    }

    public static function bounced($callback)
    {
        static::registerModelEvent('bounced', $callback);
    }

    public static function spamReported($callback)
    {
        static::registerModelEvent('spamReported', $callback);
    }
}
