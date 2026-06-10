<?php

namespace Dinubo\Mailer;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Dinubo\Mailer\Models\Newsletter;
use Dinubo\Mailer\Traits\HasPlaceholders;

class Action
{
    use HasPlaceholders;

    public string $id;

    public string $name;

    public ?Closure $sample = null;

    public ?Closure $execute = null;

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

    public function execute(Closure $execute): self
    {
        $this->execute = $execute;

        return $this;
    }

    public function process(Model $recipient, Newsletter $newsletter): array
    {
        if (! $this->execute) {
            return [];
        }

        $args = ($this->execute)($recipient, $newsletter);

        return $this->buildPlaceholders($recipient, $args);
    }
}
