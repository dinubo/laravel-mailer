<?php

namespace Dinubo\Mailer;

use Closure;
use Illuminate\Database\Eloquent\Model;

class Placeholder
{
    public string $id;

    public string|Closure $closure;

    public static function make(string $id, string|Closure $closure): self
    {
        return (new self())->id($id)->closure($closure);
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function closure(string|Closure $closure): self
    {
        $this->closure = $closure;

        return $this;
    }

    public function build(Model $recipient, array $args = []): string
    {
        if (is_callable($this->closure)) {
            // Cast to string: placeholders substitute into text, and a closure may
            // legitimately return a non-string (int, null, ...).
            return (string) ($this->closure)($recipient, ...$args);
        }

        return $this->closure;
    }
}
