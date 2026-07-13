<?php

namespace App\Commands;

use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

class AppSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively configure overtimely by running every set:* command in turn.';

    /**
     * The set commands to run, in order.
     *
     * @var list<string>
     */
    private const array SETUP_COMMANDS = [
        'set:api-key',
        'set:account-id',
        'set:user-id',
        'set:since',
        'set:table-style',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (UserConfig::isConfigured()) {
            info('overtimely is already fully configured. Update the values below or press enter to keep them.');
        } else {
            info("Running setup:");
        }

        foreach (self::SETUP_COMMANDS as $command) {
            if ($this->call($command) !== self::SUCCESS) {
                $this->warn("Setup aborted at {$command}.");

                return self::FAILURE;
            }
        }

        note('Configuration saved to '.UserConfig::path());

        return self::SUCCESS;
    }
}
