<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;

class TelegramBotController extends Controller
{
    public function __construct(protected TelegramBotService $service) {}

    public function webhook(Request $request, string $secret)
    {
        abort_unless(
            $secret !== '' && hash_equals((string) config('services.telegram.webhook_secret'), $secret),
            404
        );

        $this->service->webhook($request);

        return response()->json(['ok' => true]);
    }
}
