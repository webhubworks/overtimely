# Plan: persistent OAuth2 auth for overtimely

## Context

Today the CLI authenticates with a **bearer token copied from the Timely API docs login**, which expires after ~12h. That is fine for development but unusable for a released tool - users would have to re-paste a token twice a day.

The Timely API (`docs/timely-api-v1.yaml`) offers exactly one legitimate persistent mechanism: **OAuth 2.0 Authorization Code flow** (single scope `manage`; grants `authorization_code` + `refresh_token`; no `client_credentials`, no PKCE). Whoever runs the tool (or their admin) registers a Timely **OAuth application** and provides its `client_id` + `client_secret`; each person runs `auth:login` once, authorizes with **their own** Timely account, and receives a **per-user** access + refresh token. Nothing about a specific Timely account, customer, or OAuth app is hardcoded - the tool is reusable by any Timely organization, and webhub is just one such user that distributes its shared app's credentials to its team internally. Giving those users the Timely **`employee` role** ("Can only see, log and edit their own hours") is what restricts them to their own data - OAuth scope cannot express that, and the app cannot (and should not) enforce it. Refresh tokens keep access alive between the occasional (daily/weekly) command runs.

Scope is deliberately minimal: one login command, transparent token refresh, and a **single** `/users/current` call at setup time to learn the user's own id + account creation date. No logout/revoke, no account auto-discovery, no reactive-401 retry, no per-run identity lookups.

The OAuth app is registered with the **out-of-band (OOB) redirect** `urn:ietf:wg:oauth:2.0:oob`, so login is a copy-paste code exchange - Timely displays the authorization code on a page and the user pastes it into the CLI. No local callback server is involved.

## Goals / non-goals

- **Goal:** replace the 12h docs token with an OAuth login that stays valid via refresh tokens; all state persisted in the existing `UserConfig` JSON file.
- **Goal:** stop asking the user for their numeric user id - fetch it (and the account creation date) from `/users/current` **once at setup** and store both in the config.
- **Goal:** stay provider-agnostic - every Timely account / customer / OAuth value (`client_id`, `client_secret`, `redirect_uri`, `account_id`, and the Timely API/OAuth URLs) is supplied via env or the user config, never baked into the source, so anyone can point the tool at their own Timely OAuth app.
- **Non-goal:** logout, token revocation, multi-account selection / `/accounts` auto-discovery, reactive 401 handling, a local loopback callback server (the OOB redirect makes it unnecessary), per-run API calls to resolve identity.

## Conventions

- **No comments in the implementation.** Do not add method docblocks (`/** ... */`, `@param`/`@throws`/`@var`) or inline comments to any new or edited method - rely on clear naming. The `//` annotations in this plan's code snippets are reader notes and must not appear in the produced code. Pre-existing comments on untouched code are left as-is.

---

## 1. `UserConfig` - generalize getters/setters

File: `app/Support/UserConfig.php`

Replace the per-key typed methods (`getApiToken`/`setApiToken`, `getAccountId`, `getUserId`, ...) with a **key whitelist + one generic accessor pair**. Keep `path()`, `exists()`, `load()`, `save()` unchanged.

```php
final class UserConfig
{
    public const ACCESS_TOKEN     = 'access_token';
    public const REFRESH_TOKEN    = 'refresh_token';
    public const TOKEN_EXPIRES_AT = 'token_expires_at'; // unix ts, null = non-expiring
    public const CLIENT_ID        = 'client_id';
    public const CLIENT_SECRET    = 'client_secret';
    public const REDIRECT_URI     = 'redirect_uri';
    public const ACCOUNT_ID       = 'account_id';
    public const USER_ID          = 'user_id';
    public const CREATED_AT       = 'created_at';        // account creation date, Y-m-d (default report start)
    public const SINCE            = 'since';
    public const TABLE_STYLE      = 'table_style';

    /** @var list<string> */
    private const KEYS = [
        self::ACCESS_TOKEN, self::REFRESH_TOKEN, self::TOKEN_EXPIRES_AT,
        self::CLIENT_ID, self::CLIENT_SECRET, self::REDIRECT_URI,
        self::ACCOUNT_ID, self::USER_ID, self::CREATED_AT,
        self::SINCE, self::TABLE_STYLE,
    ];

    public static function get(string $key): mixed              // asserts $key in KEYS; returns raw value or null
    public static function set(string $key, mixed $value): void // asserts key; null/'' => unset; single save()
    public static function setMany(array $values): void         // merge many keys, one save() (used by login/identity/refresh)
    public static function isConfigured(): bool                 // refresh_token && account_id && user_id
}
```

Notes:
- `set()`/`setMany()` keep the existing null-unsets-key semantics and the `chmod 0600` write.
- An unknown key throws (guards typos now that keys are strings).
- `api_token` is removed; a stale `api_token` in an old config file is simply ignored.

## 2. `config/timely.php` - new shape

```php
return [
    'base_url' => env('TIMELY_API_URL', 'https://api.timelyapp.com/1.1/'),
    'timeout'  => env('TIMELY_API_TIMEOUT', 15),

    'oauth' => [
        'authorize_url' => env('TIMELY_OAUTH_AUTHORIZE_URL', 'https://api.timelyapp.com/1.1/oauth/authorize'),
        'token_url'     => env('TIMELY_OAUTH_TOKEN_URL',     'https://api.timelyapp.com/1.1/oauth/token'),
        'client_id'     => env('TIMELY_OAUTH_CLIENT_ID'),
        'client_secret' => env('TIMELY_OAUTH_CLIENT_SECRET'),
        'scope'         => 'manage',
        'redirect_uri'  => env('TIMELY_OAUTH_REDIRECT_URI'),
    ],

    // per-user, written by auth:login / refresh
    'access_token'     => null,
    'refresh_token'    => null,
    'token_expires_at' => null,

    // per-user, written once by set:identity
    'user_id'    => env('TIMELY_USER_ID'),
    'created_at' => null,

    'account_id' => env('TIMELY_ACCOUNT_ID'),
    'since'      => env('TIMELY_SINCE'),
];
```

> **Nothing OAuth/account-specific is baked.** `client_id`, `client_secret`, and `redirect_uri` have no default values in `config/timely.php` - they come from env or the user config, set during `auth:login`. The Timely SaaS URLs (`base_url`, `authorize_url`, `token_url`) keep `api.timelyapp.com` defaults since they are product-level, not customer-specific, but stay env-overridable for self-hosted/regional instances. The `redirect_uri` must match whatever the user registered on their OAuth app; the `auth:login` prompt suggests the OOB standard `urn:ietf:wg:oauth:2.0:oob` as its default, and the same value is sent on both the authorize URL and the token exchange.

## 3. `AppServiceProvider` - merge map + client build

File: `app/Providers/AppServiceProvider.php`

- Rebuild the `boot()` merge map (env -> user config -> default) around the `UserConfig` key constants:
  ```php
  $map = [
      ['timely.access_token',       'TIMELY_ACCESS_TOKEN',       UserConfig::ACCESS_TOKEN],
      ['timely.refresh_token',      'TIMELY_REFRESH_TOKEN',      UserConfig::REFRESH_TOKEN],
      ['timely.token_expires_at',   'TIMELY_TOKEN_EXPIRES_AT',   UserConfig::TOKEN_EXPIRES_AT],
      ['timely.oauth.client_id',    'TIMELY_OAUTH_CLIENT_ID',    UserConfig::CLIENT_ID],
      ['timely.oauth.client_secret','TIMELY_OAUTH_CLIENT_SECRET',UserConfig::CLIENT_SECRET],
      ['timely.oauth.redirect_uri', 'TIMELY_OAUTH_REDIRECT_URI', UserConfig::REDIRECT_URI],
      ['timely.account_id',         'TIMELY_ACCOUNT_ID',         UserConfig::ACCOUNT_ID],
      ['timely.user_id',            'TIMELY_USER_ID',            UserConfig::USER_ID],
      ['timely.created_at',         'TIMELY_CREATED_AT',         UserConfig::CREATED_AT],
      ['timely.since',              'TIMELY_SINCE',              UserConfig::SINCE],
      ['display.table_style',       'TABLE_STYLE',               UserConfig::TABLE_STYLE],
  ];
  ```
  Loop reads `UserConfig::get($userKey)` (env still wins).
- `register()` builds the `TimelyService` client with a **freshly-valid** token, and injects the stored identity:
  ```php
  $client = Http::baseUrl($config['base_url'])
      ->withToken(app(TimelyAuth::class)->validAccessToken())
      ->acceptJson()->timeout($config['timeout'])->retry(3, 200)->throw();

  return new TimelyService(
      $client,
      (int) $config['account_id'],
      $config['user_id'] !== null ? (int) $config['user_id'] : null,
      $config['created_at'] !== null
          ? CarbonImmutable::createFromFormat('!Y-m-d', $config['created_at'])
          : null,
  );
  ```

## 4. New DTOs

Both spatie/laravel-data, mirroring existing DTOs (`PeriodData`, `DurationData`).

- `app/DataTransferObjects/OAuthTokenData.php` - maps `V1.OAuth.TokenResponse`: `access_token`, `refresh_token` (nullable), `expires_in` (nullable int), `created_at` (int), `scope`, `token_type`. Method `expiresAt(): ?int` = `expires_in === null ? null : created_at + expires_in`.
- `app/DataTransferObjects/CurrentUserData.php` - maps the fields we need from `/users/current`: `id` (int) and `createdAt` (from the `created_at` unix timestamp). Small DTO used by `set:identity`.

## 5. New service: `TimelyAuth` (single auth class)

File: `app/Services/TimelyAuth.php` - folds the OAuth calls **and** token-refresh/persistence into one class. Uses plain `Http` (no bearer) against `config('timely.oauth')`.

- `authorizeUrl(): string` - builds `{authorize_url}?response_type=code&client_id=...&redirect_uri=...&scope=manage` from config. No `state` (OOB has no redirect to intercept).
- `exchangeCode(string $code): OAuthTokenData` - POST `token_url` with `grant_type=authorization_code`, `code`, and the config `redirect_uri`.
- `refresh(string $refreshToken): OAuthTokenData` - POST `token_url`, `grant_type=refresh_token`.
- `persist(OAuthTokenData $t): void` - `UserConfig::setMany([...access, refresh (keep old if not rotated), token_expires_at...])` in one write.
- `validAccessToken(): string` - proactive refresh:
  - no access/refresh token -> throw a clear "run `auth:login`" exception (safety net; the config guard normally catches this first);
  - `token_expires_at === null` -> return the stored access token (Timely may issue non-expiring tokens);
  - `now >= expires_at - 60s` -> `refresh()` -> `persist()` -> also `config()->set(...)` live -> return the new token;
  - otherwise return the stored token.

## 6. `TimelyService` - use stored identity (no per-run identity calls)

File: `app/Services/TimelyService.php`

- Constructor: `(PendingRequest $client, int $accountId, ?int $userId = null, ?CarbonImmutable $createdAt = null)`. `userId`/`createdAt` are nullable only so the class can be resolved during `set:identity` (before they exist); at command runtime the config guard guarantees they are set.
- `getCurrentUser(): CurrentUserData` - GET `{$accountId}/users/current` (needs only `accountId`). Used by `set:identity`; **not** called by the reporting commands.
- `getTotalLoggedHoursForPeriod()` / `getCapacities()` -> use `$this->userId` (own data only), exactly as today.
- `getCreationDate(): CarbonImmutable` -> returns the stored `$this->createdAt` (no API call). Fallback: if null (e.g. an env-only config that set `TIMELY_USER_ID` but no created date), lazily call `getCurrentUser()->createdAt` once for that run.

> **De-risk before shipping:** live-check `reports/filter` and `users/{id}/capacities` with an actual `employee`-role token to confirm own-data access is not blocked for that role.

## 7. Login transport: out-of-band (OOB), no local server

The OAuth app is registered with the OOB redirect `urn:ietf:wg:oauth:2.0:oob`, so login uses the copy-paste code flow (the spec's `/1.1/oauth/authorize/native` page) instead of a loopback HTTP server:

- No `LoopbackServer`, no `stream_socket_server`, no fixed port, no browser-callback plumbing, no `state`.
- `auth:login` prints (and optionally opens) the authorize URL; after authorizing, Timely shows the authorization code on a page, and the user pastes it back into the prompt.
- Works headless / over SSH with no extra machinery.

## 8. New command: `auth:login`

File: `app/Commands/Auth/AuthLogin.php` (Laravel Zero command, same shape as the `Set/*` commands).

1. Resolve `client_id`, `client_secret`, `redirect_uri` from env/config. If any is missing: when interactive, prompt for them once (the `redirect_uri` prompt defaults to `urn:ietf:wg:oauth:2.0:oob`) and persist to `UserConfig`; when non-interactive, error naming the `TIMELY_OAUTH_*` env vars.
2. Print `TimelyAuth::authorizeUrl()` prominently and try to open it (`open` / `xdg-open` / `start`); instruct the user to authorize and copy the code Timely displays.
3. Prompt for the authorization code.
4. `TimelyAuth::exchangeCode($code)` -> `TimelyAuth::persist()`; set the new tokens on `config()` live so a same-process `set:identity` can use them.
5. Success message (masked token). It does **not** need `account_id` (that is separate config).

## 9. New command: `set:identity` (single `/users/current` fetch)

File: `app/Commands/Set/SetIdentity.php` - persists identity fetched from the API rather than prompting. This is the one place `/users/current` is called.

- Requires a valid token + `account_id` (errors clearly, pointing at `auth:login` / `set:account-id`, if missing).
- `$user = app(TimelyService::class)->getCurrentUser();`
- `UserConfig::setMany([USER_ID => $user->id, CREATED_AT => $user->createdAt->format('Y-m-d')]);`
- Info output: resolved user id + account creation date. Re-runnable (e.g. after switching accounts).

## 10. Setup + guard wiring

- **`app/Commands/AppSetup.php`** - new step order: `auth:login`, `set:account-id`, `set:identity`, `set:since`, `set:table-style`. Remove `set:api-token` and `set:user-id`. Before `set:identity` runs in the same process, tokens/account are already persisted; `set:identity` resolves the client through the config the earlier steps set live.
- **`app/Concerns/EnsuresAppConfiguration.php`** - `hasCredentials()` checks `filled(config('timely.refresh_token'))` + `filled(config('timely.account_id'))` + `filled(config('timely.user_id'))`; route missing config to `app:setup`. Update the post-setup `config()->set(...)` block to the new keys (`access_token`, `refresh_token`, `token_expires_at`, `account_id`, `user_id`, `created_at`).
- **Delete** `app/Commands/Set/SetApiToken.php` and `app/Commands/Set/SetUserId.php` (the latter replaced by the auto-fetching `set:identity`; `TIMELY_USER_ID` env still works for CI overrides).

## 11. Tests

Existing suite: `tests/Unit/{DurationData,BalanceData,CapacityCalculationService}Test.php`.

- `OAuthTokenData`: `expiresAt()` with and without `expires_in`. `CurrentUserData`: maps `id` + `created_at` timestamp -> date.
- `UserConfig`: `get`/`set`/`setMany` round-trip; unknown-key throws; `isConfigured()` new semantics.
- `TimelyAuth` (`Http::fake`): request shape of `exchangeCode`/`refresh`; `validAccessToken()` - non-expiring token untouched, expired token refreshed + persisted, refresh-token rotation handled.
- `TimelyService` (`Http::fake`): `getCurrentUser()` parsing; reporting methods use the injected `userId`; `getCreationDate()` returns the stored date without hitting the API (and the null-fallback path does).
- `SetIdentity` command: with a faked `/users/current`, persists `user_id` + `created_at`; errors when token/account missing.
- `AuthLogin`: `authorizeUrl()` builds correctly from config; with the code prompt stubbed and `exchangeCode` faked, tokens are persisted and set live on `config()`.

## Providing OAuth app credentials (ops, not code)

The tool ships with **no** OAuth credentials. Any Timely organization can use it: an admin registers an OAuth application in their Timely account (with the OOB redirect `urn:ietf:wg:oauth:2.0:oob`), and users supply `client_id` + `client_secret` + `redirect_uri` through the `auth:login` prompts, the `TIMELY_OAUTH_*` env vars, or the local `config.json` (0600). `client_secret` is the only sensitive value; keep it out of any committed repo. For webhub specifically this is just an ops choice - the team's shared app credentials are distributed internally (env or a pre-filled config), not hardcoded in the source.

---

## Build order

1. `UserConfig` generalization (1) -> `config/timely.php` (2) -> `AppServiceProvider` (3).
2. `OAuthTokenData` + `CurrentUserData` (4) -> `TimelyAuth` (5).
3. `auth:login` (8, OOB copy-paste per section 7) -> `set:identity` (9) -> setup/guard wiring + deletions (10).
4. `TimelyService` stored-identity changes (6).
5. Tests (11) are written alongside each step above (not deferred to the end), so `./vendor/bin/pest` stays green at every commit.

## Commits

Implement in the logical steps below and commit after each one, so the app always compiles, `./vendor/bin/pest` is green, and the CLI still runs. Each commit carries its own tests. Commit messages are subject-only (no body/description), start with a lowercase present-tense verb (`adds`, `removes`, `reworks`, `switches`, ...) matching the existing history, and carry no co-author trailer.

1. **generalizes UserConfig into a key-based store** - rewrite `UserConfig` (whitelist + `get`/`set`/`setMany`) and update every caller (`AppServiceProvider`, `EnsuresAppConfiguration`, the `Set/*` commands). Still on the docs bearer token, so behaviour is unchanged and the app works.
2. **adds Timely OAuth token DTOs and auth service** - `config/timely.php` oauth keys, `OAuthTokenData`, `CurrentUserData`, `TimelyAuth`. Dormant; nothing calls it yet.
3. **adds auth:login and set:identity commands** - both commands, not yet wired into setup or the guard. Usable standalone; the old flow stays intact.
4. **switches the Timely client and setup to OAuth** - `AppServiceProvider` builds the client via `validAccessToken()`; `TimelyService` identity rework (section 6); `AppSetup` new step order; `EnsuresAppConfiguration` new checks; delete `SetApiToken` + `SetUserId`; drop the `api_token` key. Atomic cutover so the app is internally consistent and fully on OAuth.

## Verification (end-to-end)

- **Unit/feature:** `./vendor/bin/pest` - all green, including the new `TimelyAuth` / `UserConfig` / `TimelyService` / `SetIdentity` tests.
- **Login flow (live):** run `php overtimely auth:login`; on first run it prompts for `client_id`/`client_secret`/`redirect_uri` (none baked), then you authorize in the browser, paste the code Timely displays, and tokens land in `~/.config/overtimely/config.json` (0600).
- **Setup identity:** run `php overtimely app:setup` (or `set:identity` directly); confirm exactly one `/users/current` request is made and `user_id` + `created_at` are written to the config.
- **Own-data commands:** with an `employee`-role token, run `php overtimely get:total` and `get:months`; confirm they make **no** `/users/current` call and return only that user's data.
- **Refresh:** manually set `token_expires_at` to a past timestamp in the config file, run a `get:*` command, and confirm it transparently refreshes (new `access_token` / `token_expires_at` written) without re-login.
- **Guard:** move the config file aside and run `get:total` non-interactively; confirm the "not configured / run app:setup" error path.
