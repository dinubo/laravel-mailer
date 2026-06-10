<?php

namespace Dinubo\Mailer\Traits;

use Illuminate\Support\Str;

trait Uuid
{
    protected static function bootUuid()
    {
        static::updating(function ($model) {
            $model->uuid = $model->getOriginal('uuid');
        });
    }

    protected function initializeUuid()
    {
        $this->uuid = Str::uuid()->toString();
    }
}
