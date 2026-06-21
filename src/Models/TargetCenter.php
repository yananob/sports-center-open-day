<?php

declare(strict_types=1);

namespace App\Models;

class TargetCenter
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $url
    ) {}

    public static function fromArray(string $id, array $data): self
    {
        return new self(
            $id,
            $data['name'] ?? '',
            $data['url'] ?? ''
        );
    }
}
