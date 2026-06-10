<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Routing\Controller;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Message;

class TrackingOpenController extends Controller
{
    public function __invoke($refId)
    {
        /** @var Message $message */
        $message = Message::where('uuid', Mailer::toUuid($refId))->first();

        if ($message) {
            $message->opened();
        }

        $pixel = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACXBIWXMAAA7EAA'
            .'AOxAGVKw4bAAAADUlEQVQImWP4//8/AwAI/AL+hc2rNAAAAABJRU5ErkJggg=='
        );

        return response()->make($pixel)->header('Content-Type', 'image/png');
    }
}
