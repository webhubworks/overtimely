<?php

namespace App\Commands\Set;

use App\Enums\ConfigKey;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;

class SetTableStyle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:table-style {style? : One of the available table styles. [non-interactive]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets your preferred table border style for the output.';

    const array TABLE_STYLES = [
        'default',
        'compact',
        'markdown',
        'borderless',
        'symfony-style-guide',
        'box',
        'box-double',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $styleArg = $this->argument('style');

        if ($styleArg !== null && ! in_array($styleArg, self::TABLE_STYLES, true)) {
            $this->error("Unknown table style '{$styleArg}'. Choose one of: ".implode(', ', self::TABLE_STYLES));

            return self::FAILURE;
        }

        $style = $styleArg ?? select(
            label: 'Select a table style',
            options: self::TABLE_STYLES,
            default: config(ConfigKey::TableStyle->value),
        );

        UserConfig::set(ConfigKey::TableStyle, $style);
        config()->set(ConfigKey::TableStyle->value, $style);

        info("Table style set to '{$style}'.");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
