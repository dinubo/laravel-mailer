<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Routing\Controller;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Message;

class UnsubscribeController extends Controller
{
    public function __invoke($refId)
    {
        /** @var Message $message */
        $message = Message::where('uuid', Mailer::toUuid($refId))->first();

        if (! $message) {
            abort(404);
        }

        $message->unsubscribe();

        return view('mailer::unsubscribe', compact('message'));
    }
}
