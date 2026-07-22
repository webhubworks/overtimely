<?php

namespace App\Commands\Get;

use App\DataTransferObjects\BalanceData;
use App\DataTransferObjects\PeriodBalanceData;
use App\DataTransferObjects\PeriodData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class GetWeeksCommand extends GetBaseCommand
{
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
    protected $description = 'Lists all calendar weeks with a non-zero overtime balance in the given period with their individual logged hours, expected hours and overtime balance.';

    /**
     * @var Collection<int, PeriodBalanceData>
     */
    private Collection $weeks;

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    public function handle(): int
    {
        parent::handle();

        $this->line('Fetching your logged hours per week ...');
        $this->weeks = $this->period->weeks()
            ->map(fn (PeriodData $week): PeriodBalanceData => new PeriodBalanceData(
                period: $week,
                balance: BalanceData::fromOperands(
                    $this->timely->getTotalLoggedHoursForPeriod($week),
                    $this->capacity->forPeriod($week),
                ),
            ))
            ->filter(fn (PeriodBalanceData $week): bool => $week->balance->balance->totalSeconds !== 0)
            ->values();

        $rightAlignment = (new TableStyle)->setPadType(STR_PAD_LEFT);

        $this->newLine();
        $this->table(
            [
                'Year',
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
            ],
        );

        return self::SUCCESS;
    }

    private function buildWeekRows(): array
    {
        return $this->weeks
            ->groupBy(fn (PeriodBalanceData $week): string => $week->period->until->format('Y'))
            ->map(fn (Collection $yearGroup, string $year): array => $yearGroup->values()
                ->map(fn (PeriodBalanceData $week, int $index): array => $this->weekRow(
                    $week,
                    // Only the first row of a year carries the spanning year cell.
                    yearCell: $index === 0 ? new TableCell($year, ['rowspan' => $yearGroup->count()]) : null,
                ))
                ->all())
            ->values()
            ->flatMap(fn (array $rows, int $index): array => $index === 0 ? $rows : [new TableSeparator, ...$rows])
            ->concat([new TableSeparator, $this->totalsRow()])
            ->all();
    }

    private function weekRow(PeriodBalanceData $periodBalance, ?TableCell $yearCell): array
    {
        return [
            ...(filled($yearCell) ? [$yearCell] : []),
            $periodBalance->period->since->format('W')." ($periodBalance->period)",
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
            $this->weeks->count().' weeks',
            "$total->logged",
            "$total->expected",
            $total->balance->readable(true),
        ];
    }
}
