<?php

namespace App\Commands\Auth;

use App\Services\TimelyAuth;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;
use Throwable;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class AuthLogin extends Command
{
    protected $signature = 'auth:login {code? : Authorization code from Timely. [non-interactive]}';

    protected $description = 'Authorizes overtimely with your Timely account via OAuth.';

    public function handle(TimelyAuth $auth): int
    {
        if (! $this->ensureOAuthApp()) {
            return self::FAILURE;
        }

        $code = $this->argument('code');

        if ($code === null) {
            $url = $auth->authorizeUrl();

            note('Open this URL in your browser, authorize overtimely, then copy the code Timely shows you:');
            $this->line($url);
            $this->openInBrowser($url);

            $code = text(label: 'Authorization code', required: true);
        }

        try {
            $token = $auth->exchangeCode(trim($code));
        } catch (Throwable $e) {
            $this->error('Could not exchange the authorization code: '.$e->getMessage());

            return self::FAILURE;
        }

        $auth->persist($token);

        config()->set('timely.access_token', $token->accessToken);
        config()->set('timely.refresh_token', $token->refreshToken);
        config()->set('timely.token_expires_at', $token->expiresAt());

        info('Logged in to Timely.');
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }

    private function ensureOAuthApp(): bool
    {
        $clientId = config('timely.oauth.client_id');
        $clientSecret = config('timely.oauth.client_secret');
        $redirectUri = config('timely.oauth.redirect_uri');

        if (filled($clientId) && filled($clientSecret) && filled($redirectUri)) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('OAuth application is not configured. Set TIMELY_OAUTH_CLIENT_ID, TIMELY_OAUTH_CLIENT_SECRET and TIMELY_OAUTH_REDIRECT_URI.');

            return false;
        }

        if (blank($clientId)) {
            $clientId = text(label: 'Timely OAuth client ID', required: true);
        }

        if (blank($clientSecret)) {
            $clientSecret = password(label: 'Timely OAuth client secret', required: true);
        }

        if (blank($redirectUri)) {
            $redirectUri = text(label: 'OAuth redirect URI', default: 'urn:ietf:wg:oauth:2.0:oob', required: true);
        }

        UserConfig::setMany([
            UserConfig::CLIENT_ID => $clientId,
            UserConfig::CLIENT_SECRET => $clientSecret,
            UserConfig::REDIRECT_URI => $redirectUri,
        ]);

        config()->set('timely.oauth.client_id', $clientId);
        config()->set('timely.oauth.client_secret', $clientSecret);
        config()->set('timely.oauth.redirect_uri', $redirectUri);

        return true;
    }

    private function openInBrowser(string $url): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => 'open',
            'Windows' => 'start ""',
            default => 'xdg-open',
        };

        exec($command.' '.escapeshellarg($url).' > /dev/null 2>&1 &');
    }
}
