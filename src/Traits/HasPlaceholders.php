<?php

namespace Dinubo\Mailer\Traits;

use Dinubo\Mailer\Placeholder;
use Illuminate\Database\Eloquent\Model;

trait HasPlaceholders
{
    /**
     * Registered placeholders, keyed by name.
     *
     * @var array<string, Placeholder>
     */
    public array $placeholders = [];

    /**
     *
     * @param array<int, Placeholder>|Placeholder $placeholders
     */
    public function placeholders(array|Placeholder $placeholders): static
    {
        $placeholders = is_array($placeholders) ? $placeholders : [$placeholders];

        foreach ($placeholders as $placeholder) {
            $this->placeholders[$placeholder->id] = $placeholder;
        }

        return $this;
    }

    public function buildPlaceholders(Model $recipient, $args = []): array
    {
        return collect($this->placeholders)
            ->map(
                fn ($placeholder) => $placeholder->build($recipient, $args)
            )
            ->all();
    }
}
