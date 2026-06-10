<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mailer_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('address')->unique();
            $table->nullableMorphs('source');
            $table->timestamp('open_at')->nullable();
            $table->timestamp('click_at')->nullable();
            $table->timestamp('unsubscribe_at')->nullable();
            $table->timestamp('bounce_at')->nullable();
            $table->timestamp('spam_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['unsubscribe_at', 'bounce_at', 'spam_at']);
        });

        Schema::create('mailer_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('message_id')->nullable()->index();
            $table->foreignId('contact_id')->index();
            $table->nullableMorphs('receivable');
            $table->nullableMorphs('mailable');
            $table->string('category')->nullable()->index();
            $table->text('subject');
            $table->text('body')->nullable();
            $table->json('links')->nullable();
            $table->timestamp('schedule_for')->nullable()->index();
            $table->timestamp('send_at')->nullable()->index();
            $table->timestamp('open_at')->nullable()->index();
            $table->timestamp('click_at')->nullable()->index();
            $table->timestamp('unsubscribe_at')->nullable()->index();
            $table->timestamp('drop_at')->nullable()->index();
            $table->timestamp('bounce_at')->nullable()->index();
            $table->timestamp('spam_at')->nullable()->index();
            $table->timestamp('created_at')->nullable();

            $table->index(['mailable_type', 'mailable_id', 'schedule_for']);
            $table->index(['mailable_type', 'mailable_id', 'send_at']);
            $table->index(['mailable_type', 'mailable_id', 'open_at']);
            $table->index(['mailable_type', 'mailable_id', 'click_at']);
            $table->index(['mailable_type', 'mailable_id', 'unsubscribe_at']);
            $table->index(['mailable_type', 'mailable_id', 'drop_at']);
            $table->index(['mailable_type', 'mailable_id', 'bounce_at']);
            $table->index(['mailable_type', 'mailable_id', 'spam_at']);

            $table->index([
                'receivable_type',
                'receivable_id',
                'mailable_type',
                'mailable_id',
                'send_at',
                'schedule_for',
            ], 'mailer_messages_receivable_mailable_send_at_schedule_for_index');

            $table->index([
                'receivable_type',
                'receivable_id',
                'open_at',
                'click_at',
            ], 'mailer_messages_receivable_open_at_click_at_index');
        });

        Schema::create('mailer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->index();
            $table->string('category')->nullable()->index();
            $table->string('action')->nullable()->index();
            $table->string('link')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->boolean('offline')->default(false);
            $table->timestamp('action_at');

            // $table->foreignId('log_id')->nullable()->unique();

            $table->index(['message_id', 'action']);
            // $table->index(['action', 'action_at']);
        });

        Schema::create('mailer_newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('segment')->nullable();
            $table->string('event')->nullable()->index();
            $table->string('action')->nullable()->index();
            $table->string('category');
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('daily_rate')->nullable();
            $table->unsignedInteger('after_sec');
            $table->text('subject');
            $table->text('body');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailer_contacts');
        Schema::dropIfExists('mailer_messages');
        Schema::dropIfExists('mailer_logs');
        Schema::dropIfExists('mailer_newsletters');
    }
};
