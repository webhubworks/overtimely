<?php

namespace App\Commands\Auth;

use App\Concerns\OpensExternally;
use App\Enums\Setting;
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

        Setting::AccessToken->setConfigValue($token->accessToken);
        Setting::RefreshToken->setConfigValue($token->refreshToken);
        Setting::TokenExpiresAt->setConfigValue($token->expiresAt());

        info('Logged in to Timely.');
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }

    private function ensureOAuthApp(): bool
    {
        $clientId = Setting::ClientId->getConfigValue();
        $clientSecret = Setting::ClientSecret->getConfigValue();
        $redirectUri = Setting::RedirectUri->getConfigValue();

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
            [Setting::ClientId, $clientId],
            [Setting::ClientSecret, $clientSecret],
            [Setting::RedirectUri, $redirectUri],
        ]);

        Setting::ClientId->setConfigValue($clientId);
        Setting::ClientSecret->setConfigValue($clientSecret);
        Setting::RedirectUri->setConfigValue($redirectUri);

        return true;
    }
}
