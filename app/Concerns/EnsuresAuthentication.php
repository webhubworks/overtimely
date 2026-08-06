<?php

namespace App\Concerns;

use App\Support\UserConfig;

/**
 * Guards commands that need Timely credentials.
 * On command use when nothing is set via .env or the user config file, it drops the user into `app:setup`.
 */
trait EnsuresAuthentication
{
    protected function isAuthenticated(): bool
    {
        if (UserConfig::hasCredentials()) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('overtimely is not yet configured. Please run app:setup or set the corresponding environment variables.');

            return false;
        }

        $this->warn('overtimely is not yet authenticated. Running app:setup first ...');
        $this->call('app:setup');

        if (! UserConfig::isConfigured() || ! UserConfig::hasCredentials()) {
            $this->error("Command execution aborted because the setup wasn't successful. Please run app:setup again.");

            return false;
        }

        return true;
    }
}
