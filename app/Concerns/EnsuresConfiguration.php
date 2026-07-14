<?php

namespace App\Concerns;

use App\Support\UserConfig;

/**
 * Guards commands that need Timely credentials. On first use (nothing set via
 * env or the user config file) it drops the user into `app:setup` instead of
 * letting the command fail with a cryptic HTTP error.
 *
 * Call ensureConfigured() at the very top of handle(), before resolving
 * TimelyService (which is built from config('timely.*')).
 */
trait EnsuresConfiguration
{
    protected function ensureConfigured(): bool
    {
        // Effective config (env > user file > default), so a dev whose
        // credentials live only in .env is never forced through setup.
        if ($this->credentialsPresent()) {
            return true;
        }

        // No TTY (piped/CI): fail with guidance rather than hanging on a prompt.
        if (! $this->input->isInteractive()) {
            $this->error('overtimely is not configured. Run `overtimely app:setup` or set the TIMELY_* environment variables.');

            return false;
        }

        $this->warn('overtimely is not configured yet - running setup first.');
        $this->call('app:setup');

        // File-based recheck: did setup actually persist the credentials?
        if (! UserConfig::isConfigured()) {
            $this->error('Setup did not complete; aborting.');

            return false;
        }

        // AppServiceProvider::boot() merged config before this command ran, so
        // fold the freshly-saved values into config for the rest of this run.
        config()->set('timely.token', UserConfig::getApiToken());
        config()->set('timely.account_id', UserConfig::getAccountId());
        config()->set('timely.user_id', UserConfig::getUserId());

        return true;
    }

    private function credentialsPresent(): bool
    {
        return filled(config('timely.token'))
            && filled(config('timely.account_id'))
            && filled(config('timely.user_id'));
    }
}
