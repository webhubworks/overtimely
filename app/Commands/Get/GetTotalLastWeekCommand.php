<?php

namespace App\Commands\Get;

use LaravelZero\Framework\Commands\Command;

class GetTotalLastWeekCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total:last-week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A wrapper command for get:total. Sets SINCE and UNTIL to the start and end of the previous calendar week respectively.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $since = now()->subWeek()->startOfWeek()->format('Y-m-d');
        $until = now()->subWeek()->endOfWeek()->format('Y-m-d');

        return $this->call('get:total', [
            '--since' => $since,
            '--until' => $until,
        ]);
    }
}
