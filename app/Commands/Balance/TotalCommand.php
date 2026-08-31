<?php

namespace App\Commands\Balance;

use App\Console\BalanceCommand;
use App\DataTransferObjects\BalanceData;
use App\Enums\ConfigKey;
use Illuminate\Http\Client\ConnectionException;

class TotalCommand extends BalanceCommand
{
    protected $signature = 'balance:total';

    protected $description = 'Fetches your capacities and logged hours for the report period and calculates the total overtime balance.';

    /**
     * @throws ConnectionException
     */
    protected function report(): int
    {
        $this->line('Fetching your total logged hours ...');
        $totalLoggedHours = $this->timely->getTotalLoggedHoursForPeriod($this->period);

        $this->line('Calculating your total capacity ...');
        $totalCapacity = $this->capacity->forPeriod($this->period);

        $balance = BalanceData::fromOperands($totalLoggedHours, $totalCapacity);

        $this->newLine();

        if ($balance->balance->totalSeconds > 0) {
            $this->alert('You are on overtime!');
        } elseif ($balance->balance->totalSeconds < 0) {
            $this->alert('You have minus hours!');
        } else {
            $this->info('You are on time!');
            $this->newLine();
        }

        $this->table(
            [
                'Logged Hours',
                'Expected Hours',
                'Overtime Balance',
            ],
            [
                [
                    "$balance->logged",
                    "$balance->expected",
                    $balance->balance->toString(true),
                ],
            ],
            ConfigKey::TableStyle->getConfigValue(),
        );

        return self::SUCCESS;
    }
}
