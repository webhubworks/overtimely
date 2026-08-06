<?php

namespace App\Commands\Get;

use App\DataTransferObjects\BalanceData;
use Illuminate\Http\Client\ConnectionException;

class GetTotalCommand extends BaseGetCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches your capacities and logged hours for the period of SINCE to UNTIL and calculates the total overtime balance.';

    public function __construct()
    {
        $this->signature = 'get:total '.implode(' ', $this->periodOptions);

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    protected function get(): int
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
                    $balance->balance->readable(true),
                ],
            ],
            config('display.table_style'),
        );

        return self::SUCCESS;
    }
}
