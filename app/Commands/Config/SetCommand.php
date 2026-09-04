<?php

namespace App\Commands\Config;

use App\Enums\FetchMode;
use App\Enums\Setting;
use App\Enums\TableStyle;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class SetCommand extends Command
{
    protected $signature = 'config:set {setting? : The setting to change. [non-interactive]} {value? : The value to store. [non-interactive]}';

    protected $description = 'Sets one of your settings. Run it without arguments to pick a setting interactively.';

    public function handle(): int
    {
        $key = $this->resolveSetting();

        if ($key === null) {
            return self::FAILURE;
        }

        $value = $this->argument('value') ?? $this->askForValue($key);

        if ($value === null) {
            return self::FAILURE;
        }

        $error = $this->validationError($key, $value);

        if ($error !== null) {
            $this->error($error);

            return self::FAILURE;
        }

        $value = $this->cast($key, $value);

        UserConfig::set($key, $value);
        $key->setConfigValue($value);

        info("{$key->label()} set to {$value}.");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }

    private function resolveSetting(): ?Setting
    {
        $name = $this->argument('setting');

        if ($name === null) {
            if (! $this->input->isInteractive()) {
                $this->error('No setting given. '.$this->settableHint());

                return null;
            }

            $name = select(
                label: 'Which setting do you want to change?',
                options: collect(Setting::settable())
                    ->mapWithKeys(fn (Setting $setting): array => [$setting->kebabName() => $setting->label()])
                    ->all(),
            );
        }

        $setting = Setting::fromKebabName($name);

        if ($setting === null) {
            $this->error("Unknown setting '{$name}'. ".$this->settableHint());

            return null;
        }

        if (! $setting->isSettable()) {
            $this->error("The {$setting->kebabName()} setting is managed by the app and cannot be set by hand. ".$this->settableHint());

            return null;
        }

        return $setting;
    }

    private function askForValue(Setting $setting): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->error("No value given for the {$setting->kebabName()} setting.");

            return null;
        }

        return match ($setting) {
            Setting::AccountId => text(
                label: $setting->label(),
                default: (string) $setting->getConfigValue(),
                required: true,
                validate: fn (string $value): ?string => $this->validationError($setting, $value),
            ),
            Setting::ReportFetchMode => select(
                label: $setting->label(),
                options: FetchMode::values(),
                default: $setting->getConfigValue(),
                info: fn (string $value): string => FetchMode::settingInfo($value)
            ),
            Setting::ReportSince => text(
                label: $setting->label().' '.Setting::DATE_FORMATS_HINT,
                placeholder: 'first day of last month',
                default: (string) $setting->getConfigValue(),
                validate: fn (string $value): ?string => $this->validationError($setting, $value),
            ),
            Setting::ReportUntil => text(
                label: $setting->label().' '.Setting::DATE_FORMATS_HINT,
                placeholder: 'last day of last month',
                default: (string) $setting->getConfigValue(),
                validate: fn (string $value): ?string => $this->validationError($setting, $value),
            ),
            Setting::TableStyle => select(
                label: 'Select a table style',
                options: TableStyle::values(),
                default: $setting->getConfigValue(),
            ),
            default => null,
        };
    }

    private function validationError(Setting $setting, string $value): ?string
    {
        return match ($setting) {
            Setting::AccountId => ctype_digit($value)
                ? null
                : 'The account ID must be numeric.',
            Setting::ReportFetchMode => FetchMode::tryFrom($value) === null
                ? "Unknown report mode '{$value}'. Choose one of: ".implode(', ', FetchMode::values()).'.'
                : null,
            Setting::ReportSince, Setting::ReportUntil => strtotime($value) === false
                ? 'Unsupported format. '.Setting::DATE_FORMATS_HINT
                : null,
            Setting::TableStyle => TableStyle::tryFrom($value) === null
                ? "Unknown table style '{$value}'. Choose one of: ".implode(', ', TableStyle::values()).'.'
                : null,
            default => null,
        };
    }

    private function cast(Setting $setting, string $value): int|string
    {
        return $setting === Setting::AccountId ? (int) $value : $value;
    }

    private function settableHint(): string
    {
        return 'Choose one of: '.collect(Setting::settable())
            ->map(fn (Setting $setting): string => $setting->kebabName())
            ->implode(', ').'.';
    }
}
