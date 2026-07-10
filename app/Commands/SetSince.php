<?php

namespace App\Commands;

use App\Support\UserConfig;
use Carbon\CarbonImmutable;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;

class SetSince extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:since {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the default report start date (Y-m-d).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->argument('date') ?? text(
            label: 'Report start date (Y-m-d)',
            default: (string) config('timely.since'),
            placeholder: '2026-01-01',
            validate: fn (string $value): ?string => CarbonImmutable::hasFormat($value, 'Y-m-d')
                ? null
                : 'Use the format Y-m-d, e.g. 2026-01-01.',
        );

        if (! CarbonImmutable::hasFormat($date, 'Y-m-d')) {
            $this->error("Invalid date '{$date}'. Use the format Y-m-d, e.g. 2026-01-01.");

            return self::FAILURE;
        }

        UserConfig::setSince($date);

        info("Report start date set to {$date}.");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
