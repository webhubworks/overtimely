<?php

namespace App\Commands\Auth;

use App\Enums\ConfigKey;
use App\Services\TimelyAuthService;
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

    protected $description = 'Authorizes the app with your Timely account via OAuth.';

    public function handle(TimelyAuthService $auth): int
    {
        if (! $this->ensureOAuthApp()) {
            return self::FAILURE;
        }

        $code = $this->argument('code');

        if ($code === null) {
            $url = $auth->authorizeUrl();

            note('Open this URL in your browser, authorize the app, then copy the code Timely shows you:');
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

        ConfigKey::AccessToken->setConfigValue($token->accessToken);
        ConfigKey::RefreshToken->setConfigValue($token->refreshToken);
        ConfigKey::TokenExpiresAt->setConfigValue($token->expiresAt());

        info('Logged in to Timely.');
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }

    private function ensureOAuthApp(): bool
    {
        $clientId = ConfigKey::OAuthClientId->getConfigValue();
        $clientSecret = ConfigKey::OAuthClientSecret->getConfigValue();
        $redirectUri = ConfigKey::OAuthRedirectUri->getConfigValue();

        if (blank($clientId) || blank($clientSecret) || blank($redirectUri)) {
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
        }

        UserConfig::setMany([
            [ConfigKey::OAuthClientId, $clientId],
            [ConfigKey::OAuthClientSecret, $clientSecret],
            [ConfigKey::OAuthRedirectUri, $redirectUri],
        ]);

        ConfigKey::OAuthClientId->setConfigValue($clientId);
        ConfigKey::OAuthClientSecret->setConfigValue($clientSecret);
        ConfigKey::OAuthRedirectUri->setConfigValue($redirectUri);

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
