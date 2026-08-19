<?php

namespace App\Concerns;

use App\Support\UserConfig;

/**
 * Guards commands that need Timely credentials.
 * On command use when nothing is set via .env or the user config file, it drops the user into `config:setup`.
 */
trait EnsuresAuthentication
{
    protected function isAuthenticated(): bool
    {
        if (UserConfig::isConfigured()) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('The app is not yet configured. Please run config:setup or set the corresponding environment variables.');

            return false;
        }

        $this->warn('The app is not yet configured. Running config:setup first ...');
        $this->call('config:setup');

        if (! UserConfig::isConfigured()) {
            $this->error("Command execution aborted because the setup wasn't successful. Please run config:setup again.");

            return false;
        }

        return true;
    }
}
