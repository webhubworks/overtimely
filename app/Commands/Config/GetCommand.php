<?php

namespace App\Commands\Config;

use App\Enums\Setting;
use LaravelZero\Framework\Commands\Command;

class GetCommand extends Command
{
    protected $signature = 'config:get {setting : The setting to read.}';

    protected $description = 'Prints the current value of a single setting. Unlike config:list, this prints secrets in full.';

    public function handle(): int
    {
        $name = $this->argument('setting');
        $setting = Setting::fromKebabName($name);

        if ($setting === null) {
            $this->error("Unknown setting '{$name}'. Run config:list to see them all.");

            return self::FAILURE;
        }

        $value = $setting->getConfigValue();

        if (blank($value)) {
            $this->error("The {$setting->kebabName()} setting is not set.");

            return self::FAILURE;
        }

        $this->line((string) $value);

        return self::SUCCESS;
    }
}
