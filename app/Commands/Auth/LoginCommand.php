<?php

namespace App\Commands\Auth;

use App\Concerns\OpensExternally;
use App\Enums\ConfigKey;
use App\Services\TimelyAuthService;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;
use Throwable;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class LoginCommand extends Command
{
    use OpensExternally;

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
            $this->openExternally($url);

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
        $clientId = ConfigKey::ClientId->getConfigValue();
        $clientSecret = ConfigKey::ClientSecret->getConfigValue();
        $redirectUri = ConfigKey::RedirectUri->getConfigValue();

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
            [ConfigKey::ClientId, $clientId],
            [ConfigKey::ClientSecret, $clientSecret],
            [ConfigKey::RedirectUri, $redirectUri],
        ]);

        ConfigKey::ClientId->setConfigValue($clientId);
        ConfigKey::ClientSecret->setConfigValue($clientSecret);
        ConfigKey::RedirectUri->setConfigValue($redirectUri);

        return true;
    }
}
