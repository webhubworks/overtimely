<?php

namespace App\Services;

use App\DataTransferObjects\OAuthTokenData;
use App\Support\UserConfig;
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

    public function exchangeCode(string $code): OAuthTokenData
    {
        return $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('timely.oauth.redirect_uri'),
        ]);
    }

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
            UserConfig::ACCESS_TOKEN => $token->accessToken,
            UserConfig::TOKEN_EXPIRES_AT => $token->expiresAt(),
        ];

        if ($token->refreshToken !== null) {
            $values[UserConfig::REFRESH_TOKEN] = $token->refreshToken;
        }

        UserConfig::setMany($values);
    }

    public function validAccessToken(): string
    {
        $accessToken = config('timely.access_token');
        $refreshToken = config('timely.refresh_token');
        $expiresAt = config('timely.token_expires_at');

        $expired = $expiresAt !== null && now()->timestamp >= (int) $expiresAt - 60;

        if ($accessToken !== null && ! $expired) {
            return $accessToken;
        }

        if ($refreshToken === null) {
            throw new RuntimeException('Not authenticated with Timely. Run auth:login first.');
        }

        $token = $this->refresh($refreshToken);
        $this->persist($token);

        config()->set('timely.access_token', $token->accessToken);
        config()->set('timely.refresh_token', $token->refreshToken ?? $refreshToken);
        config()->set('timely.token_expires_at', $token->expiresAt());

        return $token->accessToken;
    }

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
