<?php

namespace App\Http\Resources;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $theme
 * @property mixed $id
 * @property mixed $messages
 * @property mixed $title
 * @property mixed $message
 * @property mixed $user
 * @property mixed $created_at
 * @property mixed $admin_name
 * @property mixed $status
 * @property mixed $type
 * @method messages()
 */
class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->user->phone,
            'language' => $this->user->language,
            'theme' => $this->type->name_ru,
            'app_type' => 'Telegram bot',
            'title' => $this->title,
            'message' => $this->message,
            'status' => Chat::statuses($this->status),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'admin_name' => $this->admin_name,
        ];
    }
}
