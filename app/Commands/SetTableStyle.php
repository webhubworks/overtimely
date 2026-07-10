<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;

class SetTableStyle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:table-style {style?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the table style for the output.';

    const array STYLES = [
        'default',
        'compact',
        'markdown',
        'borderless',
        'symfony-style-guide',
        'box',
        'box-double'
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $styleArg = $this->argument('style');
        $style = in_array($styleArg, self::STYLES)
            ? $styleArg
            : select( 'Select a table style', self::STYLES);


    }
}
