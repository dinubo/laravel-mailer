<?php

namespace Dinubo\Mailer;

class Segment
{
    public string $id;

    public string $name;

    public bool $random = false;

    public ?Filter $filter = null;

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

    public function random(bool $random): self
    {
        $this->random = $random;

        return $this;
    }

    public function filter(?Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }
}
