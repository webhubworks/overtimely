<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CurrentUserData extends Data
{
    public function __construct(
        public int $id,
        #[WithCast(DateTimeInterfaceCast::class, format: 'U')]
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
