<?php

namespace Dinubo\Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'mailer_logs';

    const CREATED_AT = 'action_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'category',
        'action',
        'link',
        'ip_address',
        'offline',
    ];

    protected $casts = [
        'offline' => 'boolean',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
