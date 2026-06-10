<?php

namespace Dinubo\Mailer;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Dinubo\Mailer\Console\Commands\SendNewsletters;
use Dinubo\Mailer\Listeners\ProcessOutgoingMessage;
use Dinubo\Mailer\Listeners\ProcessSentMessage;

class MailerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'mailer');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mailer');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('/mailer')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailer.php', 'mailer');

        $this->app['events']->listen(MessageSending::class, ProcessOutgoingMessage::class);
        $this->app['events']->listen(MessageSent::class, ProcessSentMessage::class);
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/mailer.php' => config_path('mailer.php'),
        ], 'mailer.config');

        // Publishing the views.
        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/mailer'),
        ], 'mailer.views');

        // Publishing the migrations.
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mailer.migrations');

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/dinubo'),
        ], 'mailer.views');*/

        // Registering package commands.
        $this->commands([
            SendNewsletters::class,
        ]);
    }
}
