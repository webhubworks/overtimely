<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\HasDateOptions;
use App\DataTransferObjects\BalanceData;
use App\DataTransferObjects\PeriodBalanceData;
use App\DataTransferObjects\PeriodData;
use App\Services\CapacityCalculationService;
use App\Services\TimelyDataService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class GetWeeks extends Command
{
    use EnsuresAppConfiguration, HasDateOptions;

    /**
     * @var Collection<int, PeriodBalanceData>
     */
    private Collection $weeks;

    private TimelyDataService $timely;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:weeks
    {--s|since= : Start of the fetched report period. Defaults to the date your Timely account was created. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
    {--u|until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all calendar weeks in the given period with their individual logged hours, expected hours and overtime balance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->isAppConfigured()) {
            return self::FAILURE;
        }

        $this->timely = app(TimelyDataService::class);

        $period = $this->parsePeriodOptions();

        if ($period->since === null || $period->until === null) {
            return self::FAILURE;
        }

        $this->info("Fetching data for period: $period");

        $this->info('Fetching your capacities ...');
        $capacity = CapacityCalculationService::fromCapacities($this->timely->getCapacities());

        $this->info('Fetching your logged hours per month ...');
        $this->weeks = $period->weeks()
            ->map(fn (PeriodData $week): PeriodBalanceData => new PeriodBalanceData(
                period: $week,
                balance: BalanceData::fromOperands(
                    $this->timely->getTotalLoggedHoursForPeriod($week),
                    $capacity->forPeriod($week),
                ),
            ),
            );

        $rightAlignment = (new TableStyle)->setPadType(STR_PAD_LEFT);

        $this->newLine();
        $this->table(
            [
                'Year',
                'Month',
                'Week',
                'Logged Hours',
                'Expected Hours',
                'Overtime Balance',
            ],
            $this->buildWeekRows(),
            config('display.table_style'),
            [
                2 => $rightAlignment,
                3 => $rightAlignment,
                4 => $rightAlignment,
                5 => $rightAlignment,
            ],
        );

        return self::SUCCESS;
    }

    private function buildWeekRows(): array
    {
        return $this->weeks
            ->groupBy(fn (PeriodBalanceData $week): string => $week->period->since->format('Y'))
            ->map(fn (Collection $yearGroup, string $year): array => $yearGroup->values()
                ->map(fn (PeriodBalanceData $month, int $index): array => $this->monthRow(
                    $month,
                    // Only the first row of a year carries the spanning year cell.
                    yearCell: $index === 0 ? new TableCell($year, ['rowspan' => $yearGroup->count()]) : null,
                ))
                ->all())
            ->values()
            ->flatMap(fn (array $rows, int $index): array => $index === 0 ? $rows : [new TableSeparator, ...$rows])
            ->concat([new TableSeparator, $this->totalsRow()])
            ->all();
    }

    private function weekRow(PeriodBalanceData $periodBalance, ?TableCell $yearCell, ?TableCell $monthCell): array
    {
        return [
            ...(filled($yearCell) ? [$yearCell] : []),
            ...(filled($monthCell) ? [$monthCell] : []),
            $periodBalance->period->since->format("W")."($periodBalance->period)",
            $periodBalance->balance->logged->tabular(),
            $periodBalance->balance->expected->tabular(),
            $periodBalance->balance->balance->tabular(true),
        ];
    }

    private function totalsRow(): array
    {
        $total = BalanceData::aggregate($this->weeks->map(fn (PeriodBalanceData $week): BalanceData => $week->balance));

        return [
            'Total',
            '',
            '',
            "$total->logged",
            "$total->expected",
            $total->balance->readable(true),
        ];
    }
}
