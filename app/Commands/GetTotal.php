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
    public function handle(TimelyService $timely, OvertimeCalculationService $overtimeCalculation): void
    {
        $since = CarbonImmutable::createFromFormat('Y-m-d', $this->option('since') ?? config('timely.since'));
        $until = $this->option('until') ? CarbonImmutable::createFromFormat('Y-m-d', $this->option('until')) : CarbonImmutable::yesterday();

        $results = $overtimeCalculation->forPeriod(
            $since,
            $until,
            $timely->getTotalLoggedHours($since, $until),
            $timely->getCapacities()
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
            'default' // Possible table styles: default, markdown, borderless, compact, symfony-style-guide, box, box-double
        );
    }

    private function formatHours(HoursData $data): string
    {
        $hoursDecimal = round($data->totalHours, 2);
        $hoursWithUnit = $data->hours !== 0 ? "{$data->hours}h " : '';
        $minutesWithUnit = $data->minutes !== 0 ? "{$data->minutes}m " : '';
        $secondsWithUnit = $data->seconds !== 0 ? "{$data->seconds}s" : '';

        return $hoursDecimal." ({$hoursWithUnit}{$minutesWithUnit}{$secondsWithUnit})";
    }
}
