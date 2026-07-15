<?php

use App\DataTransferObjects\BalanceData;
use App\DataTransferObjects\DurationData;

it('computes the balance as logged hours minus expected hours', function (float $logged, float $expected, float $balance) {
    $result = BalanceData::fromOperands(
        DurationData::fromTotalHours($logged),
        DurationData::fromTotalHours($expected),
    );

    expect($result->logged->totalHours)->toBe($logged)
        ->and($result->expected->totalHours)->toBe($expected)
        ->and($result->balance->totalHours)->toBe($balance);
})->with([
    'on target' => [40.0, 40.0, 0.0],
    'overtime' => [45.0, 40.0, 5.0],
    'minus hours' => [37.75, 40.0, -2.25],
]);
