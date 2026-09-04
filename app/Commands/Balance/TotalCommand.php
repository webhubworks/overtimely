<?php

namespace App\Commands\Balance;

use App\Console\BalanceCommand;
use App\Data\BalanceData;
use App\Data\DurationData;
use App\Enums\ConfigKey;
use App\Enums\ReportMode;
use App\Services\HoursService;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\Console\Helper\TableStyle;

class TotalCommand extends BalanceCommand
{
    protected $signature = 'balance:total';

    protected $description = 'Fetches your capacities and logged hours for the report period and calculates the total overtime balance.';

    /**
     * @throws ConnectionException
     */
    protected function report(): int
    {
        $logged = match ($this->mode) {
            ReportMode::Totals => $this->timely->getTotalHoursForPeriod($this->period),
            ReportMode::Events => $this->hours->forPeriod($this->period),
        };

        $expected = $this->capacity->forPeriod($this->period);

        $balance = BalanceData::fromOperands($logged, $expected);

        $this->newLine();

        $rightAlignment = (new TableStyle)->setPadType(STR_PAD_LEFT);

        $this->table(
            [
                'Logged Hours',
                'Expected Hours',
                'Overtime Balance',
                'Evaluation',
            ],
            [
                [
                    $balance->logged,
                    $balance->expected,
                    $balance->balance->toString(true),
                    self::evaluate($balance)
                ],
            ],
            ConfigKey::TableStyle->getConfigValue(),
            [
                0 => $rightAlignment,
                1 => $rightAlignment,
                2 => $rightAlignment,
            ]
        );

        return self::SUCCESS;
    }

    private static function evaluate(BalanceData $balance): string
    {
        if ($balance->balance->totalSeconds > 0) {
            return 'You are on overtime!';
        }

        if ($balance->balance->totalSeconds < 0) {
            return 'You have minus hours!';
        }

        return 'You are on time!';
    }

    /**
     * @throws ConnectionException
     */
    protected function buildHoursService(): HoursService
    {
        return $this->buildEventHoursService();
    }
}
