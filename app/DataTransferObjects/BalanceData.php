<?php

namespace App\DataTransferObjects;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class BalanceData extends Data
{
    public function __construct(
        public DurationData $logged,
        public DurationData $expected,
        public DurationData $balance,
    ) {}

    public static function fromOperands(DurationData $logged, DurationData $expected): self
    {
        return new self(
            logged: $logged,
            expected: $expected,
            balance: DurationData::fromTotalSeconds($logged->totalSeconds - $expected->totalSeconds),
        );
    }

    /**
     * Sum a set of balances into one: logged and expected add up, the balance
     * follows from those. Guarantees the total matches the operands it is
     * built from.
     *
     * @param  Collection<int, self>  $balances
     */
    public static function aggregate(Collection $balances): self
    {
        return self::fromOperands(
            DurationData::fromTotalSeconds($balances->sum(fn (self $balance): int => $balance->logged->totalSeconds)),
            DurationData::fromTotalSeconds($balances->sum(fn (self $balance): int => $balance->expected->totalSeconds)),
        );
    }
}
