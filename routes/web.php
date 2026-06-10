<?php

use Illuminate\Support\Facades\Route;
use Dinubo\Mailer\Http\Controllers\CallbackController;
use Dinubo\Mailer\Http\Controllers\NewsletterController;
use Dinubo\Mailer\Http\Controllers\NewsletterPreviewController;
use Dinubo\Mailer\Http\Controllers\NewsletterSendController;
use Dinubo\Mailer\Http\Controllers\NewsletterStatisticsController;
use Dinubo\Mailer\Http\Controllers\TrackingClickController;
use Dinubo\Mailer\Http\Controllers\TrackingOpenController;
use Dinubo\Mailer\Http\Controllers\UnsubscribeController;
use Dinubo\Mailer\Http\Middleware\VerifyPostmarkSignature;
use Dinubo\Mailer\Http\Middleware\VerifyResendSignature;

Route::name('mailer.')->group(function () {

    Route::middleware(config('mailer.middleware.user', []))->group(function () {
        Route::get('/open/{refId}.png', TrackingOpenController::class)->name('open');
        Route::get('/click/{refId}/{key?}', TrackingClickController::class)->name('click');
        Route::get('/unsubscribe/{refId}', UnsubscribeController::class)->name('unsubscribe');
    });

    Route::middleware(config('mailer.middleware.admin', []))->group(function () {
        Route::get('/newsletters/statistics', [NewsletterStatisticsController::class, 'index'])->name('newsletters.statistics.index');
        Route::get('/newsletters/{newsletter}/statistics', [NewsletterStatisticsController::class, 'show'])->name('newsletters.statistics.show');
        Route::post('/newsletters/{newsletter}/send', NewsletterSendController::class)->name('newsletters.send');
        Route::get('/newsletters/{newsletter}/preview', NewsletterPreviewController::class)->name('newsletters.preview');
        Route::resource('/newsletters', NewsletterController::class);
    });

    // Inbound provider webhooks. Each route verifies the provider signature/credentials
    // (Postmark Basic Auth, Resend/Svix HMAC) when the matching secret is configured;
    // otherwise the request is allowed through with a logged warning. The shared
    // `callback` middleware group remains available for any additional host middleware.
    Route::middleware(config('mailer.middleware.callback', []))->group(function () {
        Route::post('/callback/postmark', [CallbackController::class, 'postmark'])
            ->middleware(VerifyPostmarkSignature::class)
            ->name('callback.postmark');
        Route::post('/callback/resend', [CallbackController::class, 'resend'])
            ->middleware(VerifyResendSignature::class)
            ->name('callback.resend');
    });

});
