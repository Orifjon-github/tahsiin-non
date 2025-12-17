<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }
    public function start(Request $request): JsonResponse
    {
        try {
            $update = $request->all();

            // Logga yozish (debug uchun)
            Log::channel('telegram')->info('Webhook received', $update);

            // Agar callback query bo'lsa (inline tugmalar)
            if (isset($update['callback_query'])) {
                $this->telegramService->handleCallbackQuery();
                return response()->json(['ok' => true]);
            }

            // Oddiy xabar
            if (isset($update['message'])) {
                $this->telegramService->start();
                return response()->json(['ok' => true]);
            }

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::channel('telegram')->error('Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
