<?php

namespace App\Commands\Get;

use LaravelZero\Framework\Commands\Command;

class GetThisWeekCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total:this:week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "A wrapper command for get:total. Sets SINCE to the start of the current calendar week and UNTIL to today. Please note the app does not account for partially logged days or a partial daily capacity. Hours you have not worked yet will count towards minus hours for today's capacity.";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $since = now()->startOfWeek()->format('Y-m-d');
        $until = now()->format('Y-m-d');

        return $this->call('get:total', [
            '--since' => $since,
            '--until' => $until,
        ]);
    }
}
