<?php

namespace App\Commands\Set;

use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;

class SetUserId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:user-id {id? : Timely user ID. [non-interactive]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets your Timely user ID.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('id') ?? text(
            label: 'Timely user ID',
            default: (string) config('timely.user_id'),
            required: true,
            validate: fn (string $value): ?string => ctype_digit($value)
                ? null
                : 'The user ID must be numeric.',
        );

        if (! ctype_digit($id)) {
            $this->error("Invalid user ID '{$id}'. It must be numeric.");

            return self::FAILURE;
        }

        UserConfig::setUserId($id);

        info("User ID set to {$id}.");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
