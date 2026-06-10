<?php

namespace Dinubo\Mailer\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A real (persisted) recipient model for the send-pipeline test, so the
 * Message's `receivable` morph resolves to an existing table on every
 * supported Laravel version. An unsaved anonymous class leaves a null
 * morph key, which Laravel 12+ skips but Laravel 10/11 try to query.
 */
class Recipient extends Model
{
    protected $table = 'test_recipients';

    public $timestamps = false;

    protected $guarded = [];
}
