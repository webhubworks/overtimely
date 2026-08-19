<?php

namespace App\Concerns;

/**
 * Hands a URL or a file path to whichever application the operating system
 * has registered for it, without waiting for that application to exit.
 */
trait OpensExternally
{
    protected function openExternally(string $target): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => 'open',
            'Windows' => 'start ""',
            default => 'xdg-open',
        };

        exec($command.' '.escapeshellarg($target).' > /dev/null 2>&1 &');
    }
}
