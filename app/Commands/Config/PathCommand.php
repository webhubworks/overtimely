<?php

namespace App\Commands\Config;

use App\Concerns\OpensExternally;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

class PathCommand extends Command
{
    use OpensExternally;

    protected $signature = 'config:path {--o|open : Open the file with your default application.}';

    protected $description = 'Prints the path to your user config file.';

    public function handle(): int
    {
        $path = UserConfig::path();

        $this->line(sprintf('<href=%s>%s</>', self::fileUri($path), $path));

        if (! UserConfig::exists()) {
            $this->newLine();
            $this->warn('That file does not exist yet. Run config:setup to create it.');

            return self::SUCCESS;
        }

        if ($this->option('open')) {
            $this->openExternally($path);
        }

        return self::SUCCESS;
    }

    /**
     * A `file://` URI terminals can open, with each segment escaped so paths containing spaces still resolve.
     */
    private static function fileUri(string $path): string
    {
        return 'file://'.implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }
}
