<?php

namespace App\Commands\Set;

use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;

class SetAccountId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:account-id {id? : Timely account ID. [non-interactive]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets your Timely account ID.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('id') ?? text(
            label: 'Timely account ID',
            default: (string) config('timely.account_id'),
            required: true,
            validate: fn (string $value): ?string => ctype_digit($value)
                ? null
                : 'The account ID must be numeric.',
        );

        if (! ctype_digit($id)) {
            $this->error('The account ID must be numeric');

            return self::FAILURE;
        }

        UserConfig::setAccountId($id);

        info("Account ID set to {$id}.");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
