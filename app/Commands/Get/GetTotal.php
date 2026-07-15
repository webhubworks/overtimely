<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\DataTransferObjects\HoursData;
use App\Services\OvertimeBalanceCalculationService;
use App\Services\TimelyService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;

class GetTotal extends Command
{
    use EnsuresAppConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total {--since= : Start of the fetched report period. Can be omitted if a default is set via set:since (Format: YYYY-MM-DD) } {--until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches your total logged hours and capacities for the period of SINCE to UNTIL and calculates the total overtime balance.';

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    public function handle(): int
    {
        if (! $this->isAppConfigured()) {
            return self::FAILURE;
        }

        $timely = app(TimelyService::class);

        $since = CarbonImmutable::createFromFormat('Y-m-d', $this->option('since') ?? config('timely.since'));
        $until = $this->option('until') ? CarbonImmutable::createFromFormat('Y-m-d', $this->option('until')) : CarbonImmutable::yesterday();

        $overtimeCalculation = new OvertimeBalanceCalculationService($timely->getCapacities());
        $totalLoggedHours = $timely->getTotalLoggedHours($since, $until);

        $results = $overtimeCalculation->forPeriod(
            $totalLoggedHours,
            $since,
            $until,
        );

        $this->table(
            [
                'Total Logged Hours',
                'Total Expected Hours',
                'Overtime Balance',
            ],
            [
                [
                    $this->formatHours($results->logged),
                    $this->formatHours($results->expected),
                    $this->formatHours($results->balance),
                ],
            ],
            config('display.table_style'),
        );

        return self::SUCCESS;
    }

    private function formatHours(HoursData $data): string
    {
        $timeComponents = collect([
            'h' => $data->hours,
            'm' => $data->minutes,
            's' => $data->seconds,
        ])->filter()
            ->map(fn (int $value, string $unit): string => "{$value}{$unit}")
            ->implode(' ');

        $decimalHours = round($data->totalHours, 2);
        $sign = $data->totalSeconds < 0 ? '-' : '';

        return $timeComponents === ''
            ? (string) $decimalHours
            : "$decimalHours ({$sign}{$timeComponents})";
    }
}
