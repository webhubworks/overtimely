<?php

use App\DataTransferObjects\CapacityData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeCapacity(
    float $weeklyCapacity = 40.0,
    float $dailyCapacity = 8.0,
    string $workDays = 'MON,TUE,WED,THU,FRI',
    int $weeklyWorkingDays = 5,
    bool $current = true,
    string $startDate = '1970-01-01',
    ?string $endDate = null,
): CapacityData {
    return new CapacityData(
        id: null,
        weeklyCapacity: $weeklyCapacity,
        dailyCapacity: $dailyCapacity,
        weekdays: null,
        workDays: collect(explode(',', $workDays)),
        totalWorkingDays: null,
        weeklyWorkingDays: $weeklyWorkingDays,
        current: $current,
        startDate: CarbonImmutable::createFromFormat('!Y-m-d', $startDate),
        endDate: $endDate ? CarbonImmutable::createFromFormat('!Y-m-d', $endDate) : null,
    );
}
