<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\TelegramText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramTextController extends Controller
{
    use Response;
    public function index(): JsonResponse
    {
        $texts = TelegramText::all();
        return $this->success($texts);
    }

    public function show($id): JsonResponse
    {
        $text = TelegramText::findOrFail($id);
        return $this->success($text);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'uz' => 'nullable|string',
            'ru' => 'nullable|string',
            'en' => 'nullable|string',
        ]);

        $telegramText = TelegramText::findOrFail($id);

        $telegramText->update([
            'uz' => $request->input('uz', $telegramText->uz),
            'ru' => $request->input('ru', $telegramText->ru),
            'en' => $request->input('en', $telegramText->en),
        ]);

        return $this->success($telegramText);
    }
}
