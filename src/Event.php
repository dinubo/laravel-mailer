<?php

namespace Dinubo\Mailer;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Dinubo\Mailer\Models\Newsletter;
use Dinubo\Mailer\Traits\HasPlaceholders;

class Event
{
    use HasPlaceholders;

    public string $id;

    public string $name;

    public ?Closure $sample = null;

    public static function make(string $id, string $name): self
    {
        return (new self())->id($id)->name($name);
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function sample(?Closure $sample)
    {
        $this->sample = $sample;

        return $this;
    }

    public function process(Model $recipient, array $args = [])
    {
        // $placeholder = $this->buildPlaceholders($recipient, $args);

        $newsletters = Newsletter::where('is_active', true)
            ->where('event', $this->id)
            ->orderBy('after_sec')
            ->get();

        /** @var Newsletter $newsletter */
        foreach($newsletters as $newsletter) {
            $newsletter->scheduleBy($this, $recipient, $args);
        }
    }
}
