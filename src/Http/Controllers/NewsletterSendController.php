<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Dinubo\Mailer\Mail\Newsletter as MailNewsletter;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Newsletter;

class NewsletterSendController extends Controller
{
    public function __invoke(Request $request, Newsletter $newsletter)
    {
        $user = $request->user();

        $message = $newsletter->message($user);

        $message->save();

        $placeholders = [];

        $event = $newsletter->event ? (Mailer::registeredEvents()[$newsletter->event] ?? null) : null;

        if ($event) {
            $closure = $event->sample;

            $args = $closure ? $closure($user) : [];

            $placeholders = array_merge($placeholders, $event->buildPlaceholders($user, $args));
        }

        $action = $newsletter->action ? (Mailer::registeredActions()[$newsletter->action] ?? null) : null;

        if ($action) {
            $closure = $action->sample;

            $args = $closure ? $closure($user, $newsletter) : [];

            $placeholders = array_merge($placeholders, $action->buildPlaceholders($user, $args));
        }

        Mail::sendNow(new MailNewsletter($message, $placeholders));

        return redirect()->route('mailer.newsletters.show', $newsletter);
    }
}
