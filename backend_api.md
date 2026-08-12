# Weeplay Backend Requirements — Telegram Web App Authentication

This document describes the backend work required for Weeplay to authenticate users who open the website inside a Telegram Web App.

## Goal

When the frontend is opened inside Telegram:

1. Verify the Telegram Web App identity on the backend.
2. Find the Weeplay user by `telegram_id`.
3. Log in an existing user.
4. Create a new user when no matching account exists.
5. Return the same token format used by the existing Weeplay login/register endpoints.

The frontend must never be trusted to submit an arbitrary `telegram_id`, username, or phone number. The backend must validate Telegram's signed `initData`.

---

## Required database changes

Update the `users` table with the following fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `telegram_id` | string | No | Telegram user ID. Must be unique when present. |
| `phone` | string | No | Telegram Web App data normally does not contain a phone number. |
| `password` | string | No | Telegram-created accounts do not need a password initially. |

Example Laravel migration:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('telegram_id')->nullable()->unique()->after('id');
    $table->string('phone')->nullable()->change();
    $table->string('password')->nullable()->change();
});
```

Check the existing schema before applying this migration. If `phone` is currently unique, it must remain nullable and uniqueness must only apply to non-null values according to the database behavior.

### User model

Add `telegram_id` to the model's fillable fields if the project uses mass assignment:

```php
protected $fillable = [
    // existing fields...
    'telegram_id',
];
```

`telegram_id` must be treated as a string because Telegram IDs can grow beyond some integer ranges and should not lose precision when passed through JSON or JavaScript.

---

## Environment variable

The backend must have access to the Telegram bot token:

```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
```

Never expose `TELEGRAM_BOT_TOKEN` to the frontend.

The Telegram Web App must be configured for the same bot whose token is used for verification.

---

## New endpoint

### `POST /v1/auth/telegram`

Authenticates a Telegram Web App user and returns a Weeplay API token.

### Request

```json
{
  "init_data": "query_id=AA...&user=%7B%22id%22%3A123456789%7D&auth_date=1730000000&hash=..."
}
```

The value must be the raw value of:

```ts
window.Telegram.WebApp.initData
```

Do not accept only `initDataUnsafe.user.id` or a client-supplied `telegram_id`.

### Successful response

Use the same response shape as the existing login endpoint:

```http
200 OK
```

```json
{
  "message": "Telegram authentication successful.",
  "user": {
    "id": 12,
    "username": "telegram_username",
    "phone": null,
    "email": null,
    "telegram_id": "123456789"
  },
  "token": "api-token"
}
```

The token must work with the existing authenticated API routes using:

```http
Authorization: Bearer <token>
```

### Invalid request responses

Missing `init_data`:

```http
422 Unprocessable Entity
```

```json
{
  "message": "Telegram init data is required."
}
```

Invalid, tampered, or expired Telegram data:

```http
401 Unauthorized
```

```json
{
  "message": "Invalid or expired Telegram authentication data."
}
```

A Telegram user whose account cannot be created because of a conflicting existing field:

```http
409 Conflict
```

```json
{
  "message": "Unable to create a Weeplay account for this Telegram user."
}
```

---

## Telegram `initData` verification

The backend must verify the raw `init_data` according to Telegram's Web App validation rules before using any user data.

### Verification algorithm

1. Parse the query string into key/value pairs.
2. Read and remove the `hash` field.
3. Sort the remaining fields alphabetically by key.
4. Build the data-check string using:

```text
key=value
key=value
```

with one entry per line.

5. Create the secret key:

```text
secret_key = HMAC-SHA256(key="WebAppData", message=TELEGRAM_BOT_TOKEN)
```

6. Calculate:

```text
calculated_hash = HMAC-SHA256(key=secret_key, message=data_check_string)
```

7. Compare `calculated_hash` with the received `hash` using a timing-safe comparison.
8. Parse the `user` JSON field only after the hash is valid.
9. Reject data that is too old. A practical maximum age is 24 hours, using the `auth_date` field.
10. Reject the request if `user.id` is missing.

Use the official Telegram Web App validation documentation when implementing this. Do not implement a weaker custom validation scheme.

### Required extracted Telegram fields

After verification, read:

```json
{
  "id": 123456789,
  "username": "optional_username",
  "first_name": "John",
  "last_name": "Doe",
  "language_code": "en"
}
```

`id` is the authoritative identity. Telegram usernames are optional and can change.

---

## Existing user behavior

Search by the verified Telegram ID:

```text
users.telegram_id = telegram_user.id
```

If a matching user exists:

1. Do not create a second account.
2. Optionally update non-sensitive profile data such as the current Telegram username.
3. Issue a normal Weeplay API token.
4. Return the standard auth response.

Do not search by Telegram username as the primary identity because usernames are optional and changeable.

---

## New user behavior

If no user exists with the verified Telegram ID, create an account using:

| Weeplay field | Value |
| --- | --- |
| `telegram_id` | Verified Telegram `user.id`, stored as a string |
| `username` | Telegram `username` if available |
| `phone` | `null` unless separately collected and verified |
| `email` | `null` or the value allowed by the current schema |
| `password` | `null`, or an unusable random password hash |

### Username fallback

Telegram users may not have a username. Use a unique fallback such as:

```text
telegram_123456789
```

If the normal username is already used by another Weeplay user, append a safe suffix or use the Telegram ID fallback.

### Password handling

Do not store an empty plaintext password.

Preferred option:

```text
password = null
```

The normal phone/password login endpoint must reject password login when no password exists.

Alternative:

```php
$password = Hash::make(Str::random(64));
```

This creates an account with no known usable password. Provide a password setup flow later if required.

---

## Phone number handling

Telegram Web App `initData` normally does **not** include the user's phone number. The backend must not assume that it is available.

Use one of these approaches:

### Recommended: collect it later

Create the account with `phone = null`, then ask the user to add a phone number from their Weeplay profile. Verify it with the existing SMS/OTP flow if phone verification is required.

### Alternative: collect a phone during onboarding

After Telegram authentication, show a Weeplay form asking for the phone number. Validate and verify it separately before saving it.

### Telegram contact sharing

If the Telegram bot requests a shared contact, the backend must validate that the shared contact belongs to the verified Telegram user. A phone number submitted by the browser alone is not proof of ownership.

---

## Token issuing

The endpoint must issue the same type of token currently returned by:

```text
POST /v1/auth/login
POST /v1/auth/register
```

The frontend will store it and send it on later requests as:

```http
Authorization: Bearer <token>
```

Do not introduce a separate token format unless all authenticated Weeplay endpoints are updated to support it.

---

## Route and controller outline

Example Laravel route:

```php
Route::post('/auth/telegram', [AuthController::class, 'telegram']);
```

Controller outline:

```php
public function telegram(Request $request)
{
    $request->validate([
        'init_data' => ['required', 'string'],
    ]);

    $telegramUser = $this->telegramAuth->verify($request->string('init_data'));

    $user = User::where('telegram_id', (string) $telegramUser['id'])->first();

    if (!$user) {
        $username = $this->makeUniqueUsername(
            $telegramUser['username'] ?? null,
            (string) $telegramUser['id'],
        );

        $user = User::create([
            'telegram_id' => (string) $telegramUser['id'],
            'username' => $username,
            'phone' => null,
            'email' => null,
            'password' => null,
        ]);
    }

    $token = $user->createToken('telegram-web-app')->plainTextToken;

    return response()->json([
        'message' => 'Telegram authentication successful.',
        'user' => $user,
        'token' => $token,
    ]);
}
```

Adapt the token creation and user fields to the authentication package already used by the backend.

---

## Security requirements

- Never expose the Telegram bot token to the frontend.
- Never trust `initDataUnsafe` as authentication data.
- Verify the raw `initData` on every login request.
- Use a timing-safe hash comparison.
- Reject expired `auth_date` values.
- Apply rate limiting to `/v1/auth/telegram`.
- Keep `telegram_id` unique to prevent duplicate accounts.
- Log failed verification attempts without logging the bot token or complete `init_data`.
- Use HTTPS in production.
- Do not automatically merge an existing phone/email account based only on a matching username.

---

## Frontend integration contract

Once this endpoint is available, the frontend will call:

```ts
const initData = window.Telegram?.WebApp?.initData;

const response = await api.post<AuthResponse>('/v1/auth/telegram', {
  init_data: initData,
});
```

The endpoint must return `user` and `token` in the same format as the existing auth endpoints so the frontend can reuse its current token storage and authenticated API behavior.

The frontend should only attempt Telegram authentication when `window.Telegram.WebApp.initData` is present. Normal browser visitors should continue using the existing phone/password login and registration flow.
