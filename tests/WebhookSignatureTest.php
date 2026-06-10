<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Http\Middleware\VerifyPostmarkSignature;
use Dinubo\Mailer\Http\Middleware\VerifyResendSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WebhookSignatureTest extends TestCase
{
    // --- Postmark (HTTP Basic Auth) ---

    public function test_postmark_passes_with_a_warning_when_no_secret_configured(): void
    {
        config(['mailer.postmark.webhook_secret' => null]);
        Log::shouldReceive('warning')->once();

        $this->assertTrue($this->runPostmark(Request::create('/', 'POST')));
    }

    public function test_postmark_passes_with_the_correct_basic_auth_password(): void
    {
        config(['mailer.postmark.webhook_secret' => 's3cret']);

        $request = Request::create('/', 'POST', server: [
            'PHP_AUTH_USER' => 'postmark',
            'PHP_AUTH_PW' => 's3cret',
        ]);

        $this->assertTrue($this->runPostmark($request));
    }

    public function test_postmark_rejects_a_wrong_or_missing_password(): void
    {
        config(['mailer.postmark.webhook_secret' => 's3cret']);

        $this->assertRejected(fn () => $this->runPostmark(
            Request::create('/', 'POST', server: ['PHP_AUTH_PW' => 'wrong'])
        ));

        $this->assertRejected(fn () => $this->runPostmark(Request::create('/', 'POST')));
    }

    // --- Resend (Svix signature) ---

    public function test_resend_passes_with_a_warning_when_no_secret_configured(): void
    {
        config(['mailer.resend.webhook_secret' => null]);
        Log::shouldReceive('warning')->once();

        $this->assertTrue($this->runResend(Request::create('/', 'POST', content: '{}')));
    }

    public function test_resend_passes_with_a_valid_signature(): void
    {
        $secret = 'whsec_' . base64_encode('super-secret-key');
        config(['mailer.resend.webhook_secret' => $secret]);

        $this->assertTrue($this->runResend($this->signedResendRequest('{"type":"email.bounced"}', $secret)));
    }

    public function test_resend_rejects_an_invalid_signature(): void
    {
        config(['mailer.resend.webhook_secret' => 'whsec_' . base64_encode('super-secret-key')]);

        $request = $this->resendRequest('{"type":"email.bounced"}', [
            'svix-id' => 'msg_1',
            'svix-timestamp' => (string) time(),
            'svix-signature' => 'v1,not-the-right-signature',
        ]);

        $this->assertRejected(fn () => $this->runResend($request));
    }

    public function test_resend_rejects_missing_signature_headers(): void
    {
        config(['mailer.resend.webhook_secret' => 'whsec_' . base64_encode('super-secret-key')]);

        $this->assertRejected(fn () => $this->runResend(Request::create('/', 'POST', content: '{}')));
    }

    // --- helpers ---

    private function runPostmark(Request $request): bool
    {
        return (new VerifyPostmarkSignature())
            ->handle($request, fn () => new Response('passed'))
            ->getContent() === 'passed';
    }

    private function runResend(Request $request): bool
    {
        return (new VerifyResendSignature())
            ->handle($request, fn () => new Response('passed'))
            ->getContent() === 'passed';
    }

    private function signedResendRequest(string $payload, string $secret): Request
    {
        $id = 'msg_test';
        $timestamp = (string) time();
        $secretBytes = base64_decode(substr($secret, 6));
        $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$payload}", $secretBytes, true));

        return $this->resendRequest($payload, [
            'svix-id' => $id,
            'svix-timestamp' => $timestamp,
            'svix-signature' => "v1,{$signature}",
        ]);
    }

    private function resendRequest(string $payload, array $headers = []): Request
    {
        $request = Request::create('/', 'POST', content: $payload);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $request;
    }

    private function assertRejected(\Closure $call): void
    {
        try {
            $call();
            $this->fail('Expected the webhook request to be rejected with a 401.');
        } catch (HttpException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }
    }
}
