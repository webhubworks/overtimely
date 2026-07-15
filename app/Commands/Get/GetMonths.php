<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\HasDateOptions;
use App\DataTransferObjects\BalanceData;
use App\DataTransferObjects\MonthlyBalanceData;
use App\DataTransferObjects\PeriodData;
use App\Services\CapacityCalculationService;
use App\Services\TimelyService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class GetMonths extends Command
{
    use EnsuresAppConfiguration, HasDateOptions;

    /**
     * @var Collection<int, MonthlyBalanceData>
     */
    private Collection $months;

    private TimelyService $timely;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:months
    {--since= : Start of the fetched report period. Defaults to the date your Timely account was created. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
    {--until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all months in the given period with their individual logged hours, expected hours and overtime balance.';

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

        $this->timely = app(TimelyService::class);

        [$since, $until] = $this->parsePeriodOptions();

        if ($since === null || $until === null) {
            return self::FAILURE;
        }

        $this->info('Fetching your capacities ...');
        $capacity = CapacityCalculationService::fromCapacities($this->timely->getCapacities());

        $this->info('Fetching your logged hours per month ...');
        $this->months = PeriodData::months($since, $until)->map(
            fn (PeriodData $month): MonthlyBalanceData => new MonthlyBalanceData(
                month: $month,
                balance: BalanceData::fromOperands(
                    $this->timely->getTotalLoggedHoursForPeriod($month->start, $month->end),
                    $capacity->forPeriod($month->start, $month->end),
                ),
            ),
        );

        $this->newLine();
        $this->table(
            [
                'Year',
                'Month',
                'Logged Hours',
                'Expected Hours',
                'Overtime Balance',
            ],
            $this->buildMonthRows(),
            config('display.table_style'),
            // Right-align the balance column (index 4) so the durations line up.
            [4 => (new TableStyle)->setPadType(STR_PAD_LEFT)],
        );

        return self::SUCCESS;
    }

    /**
     * Rows grouped by year (the year cell spans its months), a rule between
     * year groups, and a grand total set off by a final rule.
     */
    private function buildMonthRows(): array
    {
        return $this->months
            ->groupBy(fn (MonthlyBalanceData $month): string => $month->month->start->format('Y'))
            ->map(fn (Collection $group, string $year): array => $group->values()
                ->map(fn (MonthlyBalanceData $month, int $index): array => $this->monthRow(
                    $month,
                    // Only the first row of a year carries the spanning year cell.
                    yearCell: $index === 0 ? new TableCell($year, ['rowspan' => $group->count()]) : null,
                ))
                ->all())
            ->values()
            ->flatMap(fn (array $rows, int $index): array => $index === 0 ? $rows : [new TableSeparator, ...$rows])
            ->concat([new TableSeparator, $this->totalsRow()])
            ->all();
    }

    private function monthRow(MonthlyBalanceData $month, ?TableCell $yearCell): array
    {
        // The rows a year cell covers omit the column entirely; only the first
        // row of the group prepends it.
        return [
            ...($yearCell === null ? [] : [$yearCell]),
            $month->month->start->format('F'),
            ...$this->balanceCells($month->balance),
        ];
    }

    /**
     * @param  Collection<int, MonthlyBalanceData>  $months
     */
    private function totalsRow(): array
    {
        $total = BalanceData::aggregate($this->months->map(fn (MonthlyBalanceData $month): BalanceData => $month->balance));

        return [
            new TableCell('Total', ['colspan' => 2]),
            ...$this->balanceCells($total),
        ];
    }

    private function balanceCells(BalanceData $balance): array
    {
        return [
            $balance->logged->toComponentsString(),
            $balance->expected->toComponentsString(),
            $balance->balance->toComponentsString(),
        ];
    }
}
