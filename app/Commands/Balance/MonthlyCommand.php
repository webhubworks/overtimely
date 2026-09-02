<?php

namespace App\Commands\Balance;

use App\Console\BalanceCommand;
use App\Data\BalanceData;
use App\Data\PeriodBalanceData;
use App\Data\PeriodData;
use App\Enums\ConfigKey;
use App\Services\TotalLoggedHoursService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class MonthlyCommand extends BalanceCommand
{
    protected $signature = 'balance:monthly';

    protected $description = 'Lists all calendar months in the given period with their individual logged hours, expected hours and overtime balance.';

    /**
     * @var Collection<int, PeriodBalanceData>
     */
    private Collection $months;

    /**
     * @throws ConnectionException
     */
    protected function report(): int
    {
        $this->line('Fetching your logged hours ...');
        $loggedHours = TotalLoggedHoursService::fromDailyDurations(
            $this->timely->getDailyTotalLoggedHoursForPeriod($this->period),
        );

        $this->months = $this->period->months()
            ->map(fn (PeriodData $month): PeriodBalanceData => new PeriodBalanceData(
                period: $month,
                balance: BalanceData::fromOperands(
                    $loggedHours->forPeriod($month),
                    $this->capacity->forPeriod($month),
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
            ConfigKey::TableStyle->getConfigValue(),
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
            ->groupBy(fn (PeriodBalanceData $month): string => $month->period->until->format('Y'))
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
            $month->balance->logged->toString(tabular: true),
            $month->balance->expected->toString(tabular: true),
            $month->balance->balance->toString(prefixPositive: true, tabular: true),
        ];
    }

    private function totalsRow(): array
    {
        $total = BalanceData::aggregate($this->months->map(fn (PeriodBalanceData $month): BalanceData => $month->balance));

        return [
            'Total',
            $this->months->count().' months',
            "$total->logged",
            "$total->expected",
            $total->balance->toString(prefixPositive: true),
        ];
    }
}
