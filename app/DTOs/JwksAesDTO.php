<?php

namespace App\DTOs;

class JwksAesDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $iv,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            iv:  $data['iv'],
        );
    }
}
