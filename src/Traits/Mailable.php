<?php

namespace Dinubo\Mailer\Traits;

use Dinubo\Mailer\Models\Message;

trait Mailable
{
    use HasEvents;

    public function mailerMails()
    {
        return $this->morphMany(Message::class, 'mailable');
    }
}
