<?php

namespace Dinubo\Mailer;

use Closure;
use Illuminate\Database\Eloquent\Model;

class Filter
{
    public ?Closure $query = null;

    public ?Closure $collection = null;

    public static function make(?Closure $query = null, ?Closure $collection = null): self
    {
        return (new self())->query($query)->collection($collection);
    }

    public function query(?Closure $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function collection(?Closure $collection): self
    {
        $this->collection = $collection;

        return $this;
    }
}
