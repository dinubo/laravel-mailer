<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Jobs\SendNewsletter;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Contact;
use Dinubo\Mailer\Models\Message;
use Dinubo\Mailer\Models\Newsletter;
use Dinubo\Mailer\Segment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

class SuppressionListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->run();

        Schema::create('suppression_users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });
    }

    public function test_unsubscribed_recipients_are_suppressed_case_insensitively(): void
    {
        config(['mailer.recipient_model' => SuppressionUser::class]);

        Mailer::segments(Segment::make('all', 'All Users'));

        Newsletter::create([
            'segment' => 'all',
            'category' => 'communication',
            'is_active' => true,
            'daily_rate' => 100,
            'after_sec' => 0,
            'subject' => 'x',
            'body' => 'y',
        ]);

        // Stored lower-cased contact, recipient email differs only by case.
        SuppressionUser::create(['email' => 'Ada@Example.com']);
        SuppressionUser::create(['email' => 'bob@example.com']);

        $contact = Contact::from('ada@example.com');
        $contact->unsubscribe_at = now();
        $contact->save();

        Queue::fake();

        $this->artisan('mailer:newsletters')->assertExitCode(0);

        // Only the allowed recipient (bob) is scheduled; the mixed-case unsubscribed
        // recipient (Ada) is filtered out despite the case difference.
        $this->assertSame(1, Message::count());
        Queue::assertPushed(SendNewsletter::class, 1);
    }
}

class SuppressionUser extends Model
{
    protected $table = 'suppression_users';

    protected $guarded = [];

    public $timestamps = false;

    public function mailerMails()
    {
        return $this->morphMany(Message::class, 'receivable');
    }
}
