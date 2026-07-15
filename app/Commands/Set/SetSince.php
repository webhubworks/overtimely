<?php

namespace App\Commands\Set;

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
    protected $signature = 'set:since {date? : The default report start date which is used when the --since option of the get:total command is omitted. (Format: YYYY-MM-DD) [non-interactive]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the default report start date. (Format: YYYY-MM-DD)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->argument('date') ?? text(
            label: 'Default report start date (Format: YYYY-MM-DD)',
            placeholder: now()->subYear()->format('Y-m-d'),
            default: (string) config('timely.since'),
            validate: fn (string $value): ?string => CarbonImmutable::hasFormat($value, 'Y-m-d')
                ? null
                : 'Your input has the wrong date format. Please use YYYY-MM-DD.',
        );

        if (! CarbonImmutable::hasFormat($date, 'Y-m-d')) {
            $this->error('Your input has the wrong date format. Please use YYYY-MM-DD.');

            return self::FAILURE;
        }

        UserConfig::setSince($date);

        info("Default report start date set to {$date}.");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
