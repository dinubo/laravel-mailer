<?php

namespace Dinubo\Mailer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyResendSignature
{
    /**
     * Reject webhooks whose timestamp is more than this many seconds from now
     * (Svix replay-protection window).
     */
    protected int $tolerance = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('mailer.resend.webhook_secret');

        if (empty($secret)) {
            Log::warning('Resend webhook accepted without verification: mailer.resend.webhook_secret (MAILER_RESEND_WEBHOOK_SECRET) is not set.');

            return $next($request);
        }

        $id = $request->header('svix-id');
        $timestamp = $request->header('svix-timestamp');
        $signature = $request->header('svix-signature');

        if (! $id || ! $timestamp || ! $signature) {
            abort(401, 'Missing Svix signature headers.');
        }

        if (! is_numeric($timestamp) || abs(time() - (int) $timestamp) > $this->tolerance) {
            abort(401, 'Svix timestamp outside the allowed tolerance.');
        }

        // Secret is "whsec_<base64>"; sign "<id>.<timestamp>.<rawBody>" with the decoded key.
        $secretBytes = base64_decode(
            str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret
        );

        $expected = base64_encode(
            hash_hmac('sha256', "{$id}.{$timestamp}.{$request->getContent()}", $secretBytes, true)
        );

        // svix-signature is a space-separated list of "v1,<signature>" entries.
        foreach (explode(' ', $signature) as $part) {
            $value = str_contains($part, ',') ? explode(',', $part, 2)[1] : $part;

            if (hash_equals($expected, $value)) {
                return $next($request);
            }
        }

        abort(401, 'Invalid Svix signature.');
    }
}
