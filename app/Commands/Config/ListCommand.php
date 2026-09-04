<?php

namespace App\Commands\Config;

use App\Enums\Setting;
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
            array_map($this->settingRow(...), Setting::cases()),
            Setting::TableStyle->getConfigValue(),
        );

        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }

    private function settingRow(Setting $setting): array
    {
        return [
            $setting->kebabName(),
            $this->displayValue($setting),
            $this->source($setting),
        ];
    }

    private function displayValue(Setting $setting): string
    {
        $value = $setting->getConfigValue();

        if (blank($value)) {
            return '—';
        }

        return $setting->isSecret() ? '********' : (string) $value;
    }

    private function source(Setting $setting): string
    {
        return match (true) {
            filled($setting->envValue()) => 'environment',
            filled(UserConfig::get($setting)) => 'config file',
            filled($setting->getConfigValue()) => 'default',
            default => '—',
        };
    }
}
