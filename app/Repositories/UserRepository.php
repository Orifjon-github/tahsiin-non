<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserRepository
{
    private User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function checkOrCreate(string $chat_id): array
    {
        $cacheKey = "user_{$chat_id}";

        // Check cache first
        $user = Cache::remember($cacheKey, 31536000, function () use ($chat_id) {
            return $this->model->where('chat_id', $chat_id)->first();
        });

        if ($user && $user->language !== null && $user->phone !== null && $user->status == 'active') {
            return [
                'exists' => true,
                'user' => $user
            ];
        }

        // Update or create and refresh cache
        $user = $this->model->updateOrCreate(['chat_id' => $chat_id], ['status' => 'active']);
        Cache::put($cacheKey, $user, 3600);

        return [
            'exists' => false,
            'user' => $user
        ];
    }

    public function page($chat_id, $step = null)
    {
        $cacheKey = "user_step_{$chat_id}";

        if ($step !== null) {
            // Update in DB and cache
            $user = $this->model->updateOrCreate(['chat_id' => $chat_id], ['step' => $step]);
            Cache::put($cacheKey, $step, 3600);
            return $user;
        }

        // Fetch from cache or DB
        return Cache::remember($cacheKey, 31536000, function () use ($chat_id) {
            return $this->model->where('chat_id', $chat_id)->first()?->step;
        });
    }

    public function language($chat_id, $language = null)
    {
        $cacheKey = "user_language_{$chat_id}";

        if ($language !== null) {
            // Update in DB and cache
            $user = $this->model->updateOrCreate(['chat_id' => $chat_id], ['language' => $language]);
            Cache::put($cacheKey, $language, 3600);
            return $user;
        }

        // Fetch from cache or DB
        return Cache::remember($cacheKey, 31536000, function () use ($chat_id) {
            return $this->model->where('chat_id', $chat_id)->first()?->language;
        });
    }

    public function phone($chat_id, $phone = null)
    {
        $cacheKey = "user_phone_{$chat_id}";

        if ($phone !== null) {
            // Update in DB and cache
            $user = $this->model->updateOrCreate(['chat_id' => $chat_id], ['phone' => $phone]);
            Cache::put($cacheKey, $phone, 3600);
            return $user;
        }

        // Fetch from cache or DB
        return Cache::remember($cacheKey, 31536000, function () use ($chat_id) {
            return $this->model->where('chat_id', $chat_id)->first()?->phone;
        });
    }

    public function consultation($chat_id, $consultation = null)
    {
        $cacheKey = "user_consultation_{$chat_id}";

        if ($consultation !== null) {
            // Update in DB and cache
            $user = $this->model->updateOrCreate(['chat_id' => $chat_id], ['consultation_id' => $consultation]);
            Cache::put($cacheKey, $consultation, 3600);
            return $user;
        }

        // Fetch from cache or DB
        return Cache::remember($cacheKey, 31536000, function () use ($chat_id) {
            return $this->model->where('chat_id', $chat_id)->first()?->consultation_id;
        });
    }

    public function delete($chat_id): void
    {
        $this->model->where('chat_id', $chat_id)->update(['status' => 'delete-account']);
    }
}
