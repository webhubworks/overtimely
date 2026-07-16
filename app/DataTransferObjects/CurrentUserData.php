<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class CurrentUserData extends Data
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromApi(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            createdAt: CarbonImmutable::createFromTimestamp($data['created_at']),
        );
    }
}
