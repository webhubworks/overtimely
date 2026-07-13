<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class GetLastWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:last-week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs get:total with SINCE and UNTIL set to the start and end of the last week respectively.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $since = now()->subWeek()->startOfWeek()->format('Y-m-d');
        $until = now()->subWeek()->endOfWeek()->format('Y-m-d');

        $this->call('get:total', [
            '--since' => $since,
            '--until' => $until,
        ]);
    }
}
