<?php

namespace Dinubo\Mailer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Traits\Uuid;

class Message extends Model
{
    use Uuid;

    protected $table = 'mailer_messages';

    const UPDATED_AT = null;

    protected $fillable = [
        'category',
        'subject',
        'body',
        'message_id',
    ];

    protected $casts = [
        'links' => 'array',
        'schedule_for' => 'datetime',
        'send_at' => 'datetime',
        'open_at' => 'datetime',
        'click_at' => 'datetime',
        'unsubscribe_at' => 'datetime',
        'drop_at' => 'datetime',
        'bounce_at' => 'datetime',
        'spam_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function mailable()
    {
        return $this->morphTo();
    }

    public function receivable()
    {
        return $this->morphTo()->withTrashed();
    }

    public function refId(): string
    {
        return Mailer::toPlainId($this->uuid);
    }

    public function setReceivable(?Model $receivable)
    {
        if ($receivable) {
            $this->receivable()->associate($receivable);
        }

        return $this;
    }

    public function setMailable(?Model $mailable)
    {
        if ($mailable) {
            $this->mailable()->associate($mailable);
        }

        return $this;
    }

    public function setCategory(?string $category)
    {
        $this->category = $category;

        return $this;
    }

    private function reportEvent($action)
    {
        $actions = [
            'open' => 'opened',
            'click' => 'clicked',
            'unsubscribe' => 'unsubscribed',
            'spam' => 'spamReported',
            'bounce' => 'bounced',
        ];

        $event = $actions[$action] ?? null;

        if (!$event) {
            return;
        }

        if ($this->receivable) {
            $this->receivable->fireModelEvent($event);
        }

        if ($this->mailable) {
            $this->mailable->fireModelEvent($event);
        }
    }

    private function mark($action, $options = []): void
    {
        $attribute = $action . '_at';

        $allowMultiple = in_array($action, ['open', 'click']);

        if ($this->$attribute && ! $allowMultiple) {
            return;
        }

        $log = $this->logs()->create(array_merge([
            'category' => $this->category,
            'action' => $action,
            'ip_address' => request()->ip(),
            'offline' => in_array($action, ['send', 'spam', 'bounce', 'drop']),
        ], $options));

        if (!$this->$attribute) {
            $this->$attribute = $log->action_at;
            $this->save();
        }

        $this->reportEvent($action);

        $toContact = in_array($action, ['open', 'click', 'unsubscribe', 'spam', 'bounce']);

        if (!$toContact || $this->contact->$attribute) {
            return;
        }

        $this->contact->$attribute = $log->action_at;

        if ($action === 'click' && !$this->contact->ip_address) {
            $this->contact->ip_address = request()->ip();
        }

        $this->contact->save();
    }

    public function scheduled(): void
    {
        $this->logs()->create([
            'category' => $this->category,
            'action' => 'schedule',
            'ip_address' => request()->ip(),
            'offline' => true,
        ]);
    }

    public function sent($options = []): void
    {
        $this->mark('send', $options);
    }

    public function opened($options = []): void
    {
        $this->mark('open', $options);
    }

    public function clicked($link = null, $options = []): void
    {
        $this->mark('click', array_merge(['link' => $link], $options));
    }

    public function unsubscribe(?Carbon $at = null, $offline = false, $options = []): void
    {
        if ($at !== null) {
            $options['action_at'] = $at;
        }

        $this->mark('unsubscribe', array_merge(['offline' => $offline], $options));
    }

    public function spam(?Carbon $at = null, $options = []): void
    {
        if ($at !== null) {
            $options['action_at'] = $at;
        }

        $this->mark('spam', $options);
    }

    public function bounce(?Carbon $at = null, $options = []): void
    {
        if ($at !== null) {
            $options['action_at'] = $at;
        }

        $this->mark('bounce', $options);
    }

    public function drop($options = []): void
    {
        $this->mark('drop', $options);
    }
}
