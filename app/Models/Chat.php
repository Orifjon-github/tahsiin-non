<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

/**
 * @method static where(string $string, string $string1)
 * @method static find($id)
 */
class Chat extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AppealType::class, 'appeal_type_id');
    }

    public static function statuses($status=null) {
        $lang = App::getLocale();
        $statuses = [
            'create' => [
                'uz' => 'create',
                'ru' => 'create',
                'en' => 'create'
            ],
            'ready' => [
                'uz' => 'Ready',
                'ru' => 'Ready',
                'en' => 'Ready'
            ],
            'active' => [
                'uz' => 'active',
                'ru' => 'active',
                'en' => 'active'
            ],
            'close' => [
                'uz' => 'close',
                'ru' => 'close',
                'en' => 'close'
            ],
            'complete' => [
                'uz' => 'complete',
                'ru' => 'complete',
                'en' => 'complete'
            ]
        ];

        if ($status) {
            return array_key_exists($status, $statuses) ? $statuses[$status][$lang] : $status;
        }
        return $statuses;
    }
}
