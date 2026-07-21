<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\HasDateOptions;
use App\DataTransferObjects\PeriodBalanceData;
use App\Services\TimelyDataService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;

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
        //
    }
}
