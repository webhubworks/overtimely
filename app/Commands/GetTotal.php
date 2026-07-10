<?php

namespace App\Commands;

use App\DataTransferObjects\HoursData;
use App\Services\OvertimeCalculationService;
use App\Services\TimelyService;
use Carbon\CarbonImmutable;
use LaravelZero\Framework\Commands\Command;

class GetTotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total {--since= : [YYYY-MM-DD] Can be set via an environment variable.} {--until= : [YYYY-MM-DD] Defaults to yesterday.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches your total logged hours and capacity and calculates overtime.';

    /**
     * Execute the console command.
     */
    public function handle(TimelyService $timely): void
    {
        $since = CarbonImmutable::createFromFormat('Y-m-d', $this->option('since') ?? config('timely.since'));
        $until = $this->option('until') ? CarbonImmutable::createFromFormat('Y-m-d', $this->option('until')) : CarbonImmutable::yesterday();

        $overtimeCalculation = new OvertimeCalculationService($timely->getCapacities());

        $results = $overtimeCalculation->forPeriod(
            $timely->getTotalLoggedHours($since, $until),
            $since,
            $until,
        );

        $this->table(
            ['Total Logged Hours', 'Total Expected Hours', 'Overtime Balance'],
            [
                [
                    $this->formatHours($results->logged),
                    $this->formatHours($results->expected),
                    $this->formatHours($results->balance),
                ],
            ],
            config('display.table_style'),
        );
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
