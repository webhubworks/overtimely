<?php

namespace App\Commands;

use App\Services\TimelyService;
use LaravelZero\Framework\Commands\Command;

class GetTotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches your total logged hours and capacity and calculates overtime.';

    /**
     * Execute the console command.
     */
    public function handle(TimelyService $timely)
    {
        $since = config('timely.since');
        dump($timely->getTotalLoggedHours($since));
        dump($timely->getCapacities());
    }
}
