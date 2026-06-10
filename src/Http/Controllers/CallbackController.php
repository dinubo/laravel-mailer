<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Dinubo\Mailer\Models\Message;

class CallbackController extends Controller
{
    public function postmark(Request $request)
    {
        switch ($request->RecordType) {
            case 'Bounce':
                $message = Message::where('message_id', $request->MessageID)->first();
                $message && $message->bounce();

                break;

            case 'SpamComplaint':
                $message = Message::where('message_id', $request->MessageID)->first();
                $message && $message->spam();

                break;
        }

        return response()->json(['message' => 'OK']);
    }

    public function resend(Request $request)
    {
        switch ($request->type) {
            case 'email.bounced':
                $message = Message::where('message_id', $request->data['email_id'])->first();
                $message && $message->bounce();

                break;

            case 'email.complained':
                $message = Message::where('message_id', $request->data['email_id'])->first();
                $message && $message->spam();

                break;
        }

        return response()->json(['message' => 'OK']);
    }
}
