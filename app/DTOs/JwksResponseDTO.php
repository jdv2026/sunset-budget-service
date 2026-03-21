<?php

namespace App\DTOs;

class JwksResponseDTO
{
    /** @param JwksKeyDTO[] $keys */
    public function __construct(
        public readonly string    $message,
        public readonly int       $status,
        public readonly array     $keys,
        public readonly JwksAesDTO $aes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            message: $data['message'],
            status:  $data['status'],
            keys:    array_map(fn($key) => JwksKeyDTO::fromArray($key), $data['payload']['keys']),
            aes:     JwksAesDTO::fromArray($data['payload']['aes']),
        );
    }

    public function toJwksArray(): array
    {
        return [
            'keys' => array_map(fn(JwksKeyDTO $key) => [
                'kty' => $key->kty,
                'alg' => $key->alg,
                'use' => $key->use,
                'n'   => $key->n,
                'e'   => $key->e,
            ], $this->keys),
        ];
    }
}
