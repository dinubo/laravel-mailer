<?php

namespace Dinubo\Mailer\Tests;

use Dinubo\Mailer\Event;
use Dinubo\Mailer\Jobs\SendNewsletter;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Message;
use Dinubo\Mailer\Placeholder;
use Illuminate\Database\Eloquent\Model;

class SendNewsletterJobTest extends TestCase
{
    public function test_the_queued_job_stores_the_event_id_and_is_serializable(): void
    {
        // Register an event whose placeholders carry a closure.
        $event = Event::make('signup', 'Signup')
            ->placeholders([Placeholder::make('name', fn (Model $recipient) => $recipient->name)]);

        Mailer::events($event);

        $job = new SendNewsletter(new Message(), $event->id, ['foo' => 'bar']);

        // The job holds the event *id* (string), not the closure-bearing Event object...
        $this->assertSame('signup', $job->event);

        // ...so it survives queue serialization. Storing the Event object would throw
        // "Serialization of 'Closure' is not allowed" here.
        $this->assertIsString(serialize($job));
    }
}
