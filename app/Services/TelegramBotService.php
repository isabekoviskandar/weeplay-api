<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramBotService
{
    public function __construct(protected Api $telegram) {}

    public function webhook(Request $request): void
    {
        $message = $request->input('message');

        if (! is_array($message) || ! isset($message['chat']['id'])) {
            return;
        }

        $chatId = (int) $message['chat']['id'];
        $text = $message['text'] ?? null;

        if (is_string($text) && str_starts_with($text, '/start')) {
            $this->requestPhoneNumber($chatId);

            return;
        }

        $contact = $message['contact'] ?? null;

        if (! is_array($contact) || ! isset($contact['phone_number'])) {
            return;
        }

        if ((string) ($contact['user_id'] ?? '') !== (string) ($message['from']['id'] ?? '')) {
            $this->sendMessage($chatId, 'Please use the button to send your own phone number.');

            return;
        }

        $user = $this->findUserByPhone((string) $contact['phone_number']);

        if (! $user) {
            $this->sendMessage($chatId, 'This phone number is not registered in Weeplay.', Keyboard::remove());

            return;
        }

        $user->update(['telegram_id' => $chatId]);

        $this->sendMessage($chatId, 'Your Telegram account has been linked successfully.', Keyboard::remove());
    }

    public function notifyVenueOwnerAboutBooking(Venue $venue, Booking $booking): void
    {
        /** @var User|null $owner */
        $owner = $venue->user;

        if (! $owner?->telegram_id) {
            return;
        }

        /** @var User|null $customer */
        $customer = $booking->user;
        $message = implode("\n", [
            'New booking received',
            "Venue: {$venue->name}",
            "Date: {$booking->date}",
            "Time: {$booking->from_time} - {$booking->to_time}",
            "Price: {$booking->price}",
            "Customer: {$customer?->username}",
            "Phone: {$customer?->phone}",
        ]);

        try {
            $this->sendMessage((int) $owner->telegram_id, $message);
        } catch (\Throwable $exception) {
            Log::warning('Booking created, but Telegram notification failed.', [
                'booking_id' => $booking->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function requestPhoneNumber(int $chatId): void
    {
        $keyboard = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button([
                    'text' => 'Send phone number',
                    'request_contact' => true,
                ]),
            ]);

        $this->sendMessage($chatId, 'Please send the phone number you used to register in Weeplay.', $keyboard);
    }

    private function findUserByPhone(string $phone): ?User
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return User::query()
            ->whereIn('phone', array_unique([$phone, $digits, '+'.$digits]))
            ->first();
    }

    private function sendMessage(int $chatId, string $text, ?Keyboard $keyboard = null): void
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->telegram->sendMessage($params);
    }
}
