<?php

namespace App\Services;

use App\Data\OAuthTokenData;
use App\Enums\Setting;
use App\Support\UserConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class TimelyAuthService
{
    public function authorizeUrl(): string
    {
        $oauth = config('timely.oauth');

        return $oauth['authorize_url'].'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $oauth['client_id'],
            'redirect_uri' => $oauth['redirect_uri'],
            'scope' => $oauth['scope'],
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function exchangeCode(string $code): OAuthTokenData
    {
        return $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => Setting::RedirectUri->getConfigValue(),
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function refresh(string $refreshToken): OAuthTokenData
    {
        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    public function persist(OAuthTokenData $token): void
    {
        $values = [
            [Setting::AccessToken, $token->accessToken],
            [Setting::TokenExpiresAt, $token->expiresAt()],
        ];

        if ($token->refreshToken !== null) {
            $values[] = [Setting::RefreshToken, $token->refreshToken];
        }

        UserConfig::setMany($values);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     * @throws RuntimeException
     */
    public function validAccessToken(): string
    {
        $accessToken = Setting::AccessToken->getConfigValue();
        $refreshToken = Setting::RefreshToken->getConfigValue();
        $expiresAt = Setting::TokenExpiresAt->getConfigValue();

        $expired = filled($expiresAt) && now()->timestamp >= (int) $expiresAt - 60;

        if (filled($accessToken) && ! $expired) {
            return $accessToken;
        }

        if (blank($refreshToken)) {
            throw new RuntimeException('Not authenticated with Timely. Run auth:login first.');
        }

        $token = $this->refresh($refreshToken);
        $this->persist($token);

        Setting::AccessToken->setConfigValue($token->accessToken);
        Setting::RefreshToken->setConfigValue($token->refreshToken ?? $refreshToken);
        Setting::TokenExpiresAt->setConfigValue($token->expiresAt());

        return $token->accessToken;
    }

    /**
     * @param  array<string, string>  $payload
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    private function requestToken(array $payload): OAuthTokenData
    {
        $oauth = config('timely.oauth');

        return OAuthTokenData::from(
            Http::asJson()
                ->acceptJson()
                ->post($oauth['token_url'], array_merge($payload, [
                    'client_id' => $oauth['client_id'],
                    'client_secret' => $oauth['client_secret'],
                ]))
                ->throw()
                ->json()
        );
    }
}
