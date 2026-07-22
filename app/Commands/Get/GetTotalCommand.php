<?php

namespace App\Commands\Get;

use App\DataTransferObjects\BalanceData;
use Illuminate\Http\Client\ConnectionException;

class GetTotalCommand extends GetBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total
    {--s|since= : Start of the fetched report period. Defaults to the date your Timely account was created. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
    {--u|until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches your capacities and total logged hours for the period of SINCE to UNTIL and calculates the total overtime balance.';

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    public function handle(): int
    {
        parent::handle();

        $this->info('Fetching your total logged hours ...');
        $totalLoggedHours = $this->timely->getTotalLoggedHoursForPeriod($this->period);

        $this->info('Calculating your total capacity ...');
        $totalCapacity = $this->capacity->forPeriod($this->period);

        $balance = BalanceData::fromOperands($totalLoggedHours, $totalCapacity);

        $this->newLine();

        $this->table(
            [
                'Total Logged Hours',
                'Total Expected Hours',
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
