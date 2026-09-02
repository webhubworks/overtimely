<?php

namespace App\Commands\Balance;

use App\Console\BalanceCommand;
use App\Data\BalanceData;
use App\Data\PeriodBalanceData;
use App\Data\PeriodData;
use App\Enums\ConfigKey;
use App\Services\LoggedHoursService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class WeeklyCommand extends BalanceCommand
{
    protected $signature = 'balance:weekly';

    protected $description = 'Lists all calendar weeks with a non-zero overtime balance in the given period with their individual logged hours, expected hours and overtime balance.';

    /**
     * @var Collection<int, PeriodBalanceData>
     */
    private Collection $weeks;

    /**
     * @throws ConnectionException
     */
    protected function report(): int
    {
        $this->line('Fetching your logged hours ...');
        $loggedHours = LoggedHoursService::fromDailyDurations(
            $this->timely->getDailyTotalLoggedHoursForPeriod($this->period),
        );

        $this->weeks = $this->period->weeks()
            ->map(fn (PeriodData $week): PeriodBalanceData => new PeriodBalanceData(
                period: $week,
                balance: BalanceData::fromOperands(
                    $loggedHours->forPeriod($week),
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
            ConfigKey::TableStyle->getConfigValue(),
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
            ->concat($this->weeks->isEmpty()
                ? [$this->totalsRow()]
                : [new TableSeparator, $this->totalsRow()])
            ->all();
    }

    private function weekRow(PeriodBalanceData $periodBalance, ?TableCell $yearCell): array
    {
        return [
            ...(filled($yearCell) ? [$yearCell] : []),
            $periodBalance->period->since->format('W')." ($periodBalance->period)",
            $periodBalance->balance->logged->toString(tabular: true),
            $periodBalance->balance->expected->toString(tabular: true),
            $periodBalance->balance->balance->toString(prefixPositive: true, tabular: true),
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
            $total->balance->toString(prefixPositive: true),
        ];
    }
}
