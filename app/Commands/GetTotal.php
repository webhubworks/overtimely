<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
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
    public function handle()
    {
        //
    }
}
