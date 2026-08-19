<?php

namespace App\Commands\Config;

use App\Enums\ConfigKey;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\note;

class ListCommand extends Command
{
    protected $signature = 'config:list';

    protected $description = 'Lists every setting with its current value and where that value came from. Secrets are masked.';

    public function handle(): int
    {
        $this->newLine();
        $this->table(
            [
                'Setting',
                'Value',
                'Source',
            ],
            array_map($this->settingRow(...), ConfigKey::cases()),
            ConfigKey::TableStyle->getConfigValue(),
        );

        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }

    private function settingRow(ConfigKey $key): array
    {
        return [
            $key->settingName(),
            $this->displayValue($key),
            $this->source($key),
        ];
    }

    private function displayValue(ConfigKey $key): string
    {
        $value = $key->getConfigValue();

        if (blank($value)) {
            return '—';
        }

        return $key->isSecret() ? '********' : (string) $value;
    }

    private function source(ConfigKey $key): string
    {
        return match (true) {
            filled($key->envValue()) => 'environment',
            filled(UserConfig::get($key)) => 'config file',
            filled($key->getConfigValue()) => 'default',
            default => '—',
        };
    }
}
