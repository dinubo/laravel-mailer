<?php

namespace Dinubo\Mailer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyPostmarkSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('mailer.postmark.webhook_secret');

        if (empty($secret)) {
            Log::warning('Postmark webhook accepted without verification: mailer.postmark.webhook_secret (MAILER_POSTMARK_WEBHOOK_SECRET) is not set.');

            return $next($request);
        }

        // Postmark authenticates webhooks via HTTP Basic Auth: configure the webhook
        // URL as https://<user>:<secret>@host/... and the secret arrives as the password.
        if (! hash_equals($secret, (string) $request->getPassword())) {
            abort(401, 'Invalid Postmark webhook credentials.');
        }

        return $next($request);
    }
}
