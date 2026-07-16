<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class OAuthTokenData extends Data
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public ?int $expiresIn,
        public int $createdAt,
        public string $scope,
        public string $tokenType,
    ) {}

    public function expiresAt(): ?int
    {
        return $this->expiresIn === null ? null : $this->createdAt + $this->expiresIn;
    }
}
