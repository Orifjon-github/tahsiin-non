<?php

namespace App\Repositories;

use App\Models\AppealType;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AppealRepository
{
    private Chat $model;
    private AppealType $appealModel;

    public function __construct(Chat $model, AppealType $appealModel)
    {
        $this->model = $model;
        $this->appealModel = $appealModel;
    }

    public function getAppealType($attr, $value)
    {
        $cacheKey = "appeal_type_{$attr}_{$value}";

        return Cache::remember($cacheKey, 31536000, function () use ($attr, $value) {
            return $this->appealModel->where($attr, $value)->where('enable', 1)->first();
        });
    }

    public function updateOrCreateAppeal($chat_id, array $data)
    {
        $user = User::where('chat_id', $chat_id)->first();
        $chat = $user->chats()->where('status', 'create')->first();

        if ($chat) {
            $chat->update($data);
            return $chat;
        } else {
            return $user->chats()->create($data);
        }
    }

    public function appealMessage($chat_id, $text)
    {
        $user = User::where('chat_id', $chat_id)->first();
        $chat = $user->chats()->latest()->first();

        if ($chat) {
            return $chat->messages()->create(['message' => $text]);
        }

        return false;
    }
}
