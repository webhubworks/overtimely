<?php

namespace App\Commands\Get;

use LaravelZero\Framework\Commands\Command;

class GetTotalLastMonthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total:last-month';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A wrapper command for get:total. Sets SINCE and UNTIL to the start and end of the previous calendar month respectively.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $since = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $until = now()->subMonth()->endOfMonth()->format('Y-m-d');

        return $this->call('get:total', [
            '--since' => $since,
            '--until' => $until,
        ]);
    }
}
