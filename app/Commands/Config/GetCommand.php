<?php

namespace App\Commands\Config;

use App\Enums\ConfigKey;
use LaravelZero\Framework\Commands\Command;

class GetCommand extends Command
{
    protected $signature = 'config:get {setting : The setting to read.}';

    protected $description = 'Prints the current value of a single setting. Unlike config:list, this prints secrets in full.';

    public function handle(): int
    {
        $name = $this->argument('setting');
        $key = ConfigKey::fromSettingName($name);

        if ($key === null) {
            $this->error("Unknown setting '{$name}'. Run config:list to see them all.");

            return self::FAILURE;
        }

        $value = $key->getConfigValue();

        if (blank($value)) {
            $this->error("The {$key->settingName()} setting is not set.");

            return self::FAILURE;
        }

        $this->line((string) $value);

        return self::SUCCESS;
    }
}
