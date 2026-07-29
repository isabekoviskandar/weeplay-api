<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Register the webhook URL with Telegram';

    public function handle(Api $telegram): int
    {
        $secret = config('services.telegram.webhook_secret');
        $url = config('app.url')."/api/webhook/telegram/{$secret}";

        $this->info("Setting webhook to: {$url}");

        $result = $telegram->setWebhook([
            'url' => $url,
            'allowed_updates' => ['message', 'my_chat_member', 'channel_post', 'edited_channel_post', 'message_reaction', 'message_reaction_count'],
            'drop_pending_updates' => true,
        ]);

        if ($result) {
            $this->info('Webhook set successfully.');

            return self::SUCCESS;
        }

        $this->error('Failed to set webhook.');

        return self::FAILURE;
    }
}
