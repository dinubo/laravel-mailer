<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Routing\Controller;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Message;

class TrackingClickController extends Controller
{
    public function __invoke($refId, $key = null)
    {
        /** @var Message $message */
        $message = Message::where('uuid', Mailer::toUuid($refId))->first();

        $url = url('');

        if (!$message) {
            return response()->redirectTo($url);
        }

        $key = $key ? Mailer::toUuid($key) : null;

        $links = $message->links ?? [];

        if ($key && key_exists($key, $links)) {
            $url = $links[$key];

            $message->clicked($key);
        } else {
            $message->clicked();
        }

        return response()->redirectTo($url);
    }
}
