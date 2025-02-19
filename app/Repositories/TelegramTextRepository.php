<?php

namespace App\Repositories;

use App\Models\TelegramText;
use Illuminate\Support\Facades\Cache;

class TelegramTextRepository
{
    private TelegramText $model;

    public function __construct(TelegramText $model)
    {
        $this->model = $model;
    }

    public function getOrCreate(string $keyword, string $language): string
    {
        $cacheKey = "telegram_text_{$keyword}_{$language}";

        return Cache::remember($cacheKey, 31536000, function () use ($keyword, $language) {
            $record = $this->model->firstOrCreate(
                ['keyword' => $keyword],
                ['keyword' => $keyword, 'ru' => $keyword, 'en' => $keyword, 'uz' => $keyword]
            );

            return str_replace('\\n', "\n", $record->$language);
        });
    }

    public function getKeyword($text, $language)
    {
        $cacheKey = "telegram_text_keyword_{$language}_{$text}";

        return Cache::remember($cacheKey, 31536000, function () use ($text, $language) {
            $keyword = $this->model->where($language, $text)->first();
            return $keyword ? $keyword->keyword : false;
        });
    }

    public function checkTextWithKeyboard(string $text, string $keyboard = 'register_button'): bool
    {
        $cacheKey = "telegram_text_keyboard_{$keyboard}";

        $model = Cache::remember($cacheKey, 31536000, function () use ($keyboard) {
            return $this->model->where('keyword', $keyboard)->first();
        });

        if (!$model) return false;

        return in_array($text, [$model->ru, $model->en, $model->uz]);
    }

    public function successAcceptText($lang, $id, $datetime): string
    {
        $cacheKey = "telegram_success_text_{$lang}_{$id}_{$datetime}";

        return Cache::remember($cacheKey, 31536000, function () use ($lang, $id, $datetime) {
            $text = [
                'uz' => "Rahmat. Sizning murojaat bankka muvaffaqiyatli yuborildi.\nOperatorning javobini kuting.\nMurojaat raqami: $id\nVaqt: $datetime",
                'ru' => "Спасибо. Ваша заявка успешно отправлена в банк.\nДождитесь ответа оператора.\nНомер заявки: $id\nВремя: $datetime",
                'en' => "Thank you. Your application has been successfully sent to the bank.\nWait for the operator's response.\nApplication number: $id\nTime: $datetime"
            ];

            return $text[$lang];
        });
    }
}
