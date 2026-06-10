<?php

namespace Dinubo\Mailer\Models;

use Illuminate\Database\Eloquent\Model;
use Dinubo\Mailer\Traits\Uuid;
use Illuminate\Support\Str;

class Contact extends Model
{
    use Uuid;

    protected $table = 'mailer_contacts';

    protected $fillable = [
        'address',
    ];

    protected $casts = [
        'open_at' => 'datetime',
        'click_at' => 'datetime',
        'unsubscribe_at' => 'datetime',
        'bounce_at' => 'datetime',
        'spam_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public static function from(string $address, ?Model $source = null): self
    {
        $contact = static::firstOrNew([
            'address' => Str::lower($address),
        ]);

        if ($source && !$contact->source) {
            $contact->source()->associate($source);
        }

        $contact->save();

        return $contact;
    }

    public static function checkAddress(string $address): bool
    {
        return static::query()
            ->where('address', Str::lower($address))
            ->where(function($query) {
                $query->whereNotNull('unsubscribe_at')
                    ->orWhereNotNull('bounce_at')
                    ->orWhereNotNull('spam_at');
            })
            ->doesntExist();
    }

    public function allowed(): bool
    {
        return !($this->unsubscribe_at || $this->bounce_at || $this->spam_at);
    }
}
