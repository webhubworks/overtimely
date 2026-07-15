<?php

namespace App\Casts;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Casts a delimiter-separated string (e.g. "MON,TUE,WED") into a list of
 * trimmed, non-empty values. The separator is configurable, mirroring how
 * DateTimeInterfaceCast takes a `format`:
 *
 *   #[WithCast(SeparatedStringCast::class, separator: ',')]
 */
class CollectionFromSeparatedStringCast implements Cast
{
    public function __construct(
        protected string $separator = ',',
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Collection
    {
        if (is_array($value) || $value instanceof Collection) {
            return collect($value);
        }

        if (! is_string($value) || trim($value) === '') {
            return collect();
        }

        return collect(explode($this->separator, $value))
            ->map(fn (string $token): string => trim($token))
            ->filter()
            ->values();
    }
}
