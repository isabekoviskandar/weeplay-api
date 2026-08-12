<?php

namespace App\Services;

class TelegramWebAppAuthService
{
    private const MAX_AGE_SECONDS = 86400;

    /**
     * @return array{id: int|string, username?: string}|null
     */
    public function verify(string $initData): ?array
    {
        $botToken = config('services.telegram.bot_token');

        if (! is_string($botToken) || $botToken === '') {
            return null;
        }

        parse_str($initData, $data);

        $hash = $data['hash'] ?? null;
        $authDate = $data['auth_date'] ?? null;

        if (! is_string($hash) || ! is_scalar($authDate) || ! ctype_digit((string) $authDate)) {
            return null;
        }

        $authTimestamp = (int) $authDate;

        if ($authTimestamp > time() || time() - $authTimestamp > self::MAX_AGE_SECONDS) {
            return null;
        }

        unset($data['hash']);

        foreach ($data as $value) {
            if (! is_scalar($value)) {
                return null;
            }
        }

        ksort($data, SORT_STRING);

        $dataCheckString = collect($data)
            ->map(fn (mixed $value, string $key) => $key.'='.$value)
            ->implode("\n");
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculatedHash, $hash) || ! isset($data['user'])) {
            return null;
        }

        $user = json_decode($data['user'], true);

        if (! is_array($user) || ! isset($user['id']) || ! is_int($user['id']) && ! ctype_digit((string) $user['id'])) {
            return null;
        }

        return $user;
    }
}
