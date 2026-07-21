<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\HasDateOptions;
use App\DataTransferObjects\BalanceData;
use App\DataTransferObjects\PeriodBalanceData;
use App\DataTransferObjects\PeriodData;
use App\Services\CapacityCalculationService;
use App\Services\TimelyDataService;
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
     * @var Collection<int, PeriodBalanceData>
     */
    private Collection $months;

    private TimelyDataService $timely;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:months
    {--s|since= : Start of the fetched report period. Defaults to the date your Timely account was created. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
    {--u|until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

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

        $this->timely = app(TimelyDataService::class);

        $period = $this->parsePeriodOptions();

        if ($period->since === null || $period->until === null) {
            return self::FAILURE;
        }

        $this->info("Fetching data for period from $period.");

        $this->info('Fetching your capacities ...');
        $capacity = CapacityCalculationService::fromCapacities($this->timely->getCapacities());

        $this->info('Fetching your logged hours per month ...');
        $this->months = $period->months()
            ->map(fn (PeriodData $month): PeriodBalanceData => new PeriodBalanceData(
                period: $month,
                balance: BalanceData::fromOperands(
                    $this->timely->getTotalLoggedHoursForPeriod($month),
                    $capacity->forPeriod($month),
                ),
            ),
            );

        $rightAlignment = (new TableStyle)->setPadType(STR_PAD_LEFT);

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
            [
                2 => $rightAlignment,
                3 => $rightAlignment,
                4 => $rightAlignment,
            ],
        );

        return self::SUCCESS;
    }

    /**
     * Rows grouped by year (the year cell spans its months),
     * a rule between year groups, and a grand total set off by a final rule.
     */
    private function buildMonthRows(): array
    {
        return $this->months
            ->groupBy(fn (PeriodBalanceData $month): string => $month->period->since->format('Y'))
            ->map(fn (Collection $group, string $year): array => $group->values()
                ->map(fn (PeriodBalanceData $month, int $index): array => $this->monthRow(
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

    /**
     * The rows a year cell covers omit the column entirely; only the first row of the group prepends it.
     */
    private function monthRow(PeriodBalanceData $month, ?TableCell $yearCell): array
    {

        return [
            ...(filled($yearCell) ? [$yearCell] : []),
            $month->period->since->format('F'),
            $month->balance->logged->tabular(),
            $month->balance->expected->tabular(),
            $month->balance->balance->tabular(true),
        ];
    }

    private function totalsRow(): array
    {
        $total = BalanceData::aggregate($this->months->map(fn (PeriodBalanceData $month): BalanceData => $month->balance));

        return [
            'Total',
            '',
            "$total->logged",
            "$total->expected",
            $total->balance->readable(true),
        ];
    }
}
