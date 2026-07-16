<?php

namespace App\Concerns;

use App\Support\UserConfig;

/**
 * Guards commands that need Timely credentials. On first use (nothing set via
 * .env or the user config file), it drops the user into `app:setup` instead of
 * letting the command fail.
 */
trait EnsuresAppConfiguration
{
    protected function isAppConfigured(): bool
    {
        if ($this->hasCredentials()) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('overtimely is not yet configured. Please run app:setup or set the corresponding environment variables.');

            return false;
        }

        $this->warn('overtimely is not yet configured. Running app:setup first ...');
        $this->call('app:setup');

        if (! UserConfig::isConfigured()) {
            $this->error("Command execution aborted because the setup wasn't successful. Please run app:setup again.");

            return false;
        }

        /**
         * AppServiceProvider::boot() merged the config before this command ran,
         * so we load the saved JSON values into the config here for the rest of this runtime.
         */
        config()->set('timely.token', UserConfig::get(UserConfig::API_TOKEN));
        config()->set('timely.account_id', UserConfig::get(UserConfig::ACCOUNT_ID));
        config()->set('timely.user_id', UserConfig::get(UserConfig::USER_ID));

        return true;
    }

    private function hasCredentials(): bool
    {
        return filled(config('timely.token'))
            && filled(config('timely.account_id'))
            && filled(config('timely.user_id'));
    }
}
