<?php

namespace App\DTOs;

class JwksKeyDTO
{
    public function __construct(
        public readonly string $kty,
        public readonly string $alg,
        public readonly string $use,
        public readonly string $n,
        public readonly string $e,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            kty: $data['kty'],
            alg: $data['alg'],
            use: $data['use'],
            n:   $data['n'],
            e:   $data['e'],
        );
    }
}
