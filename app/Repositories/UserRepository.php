<?php

// app/Repositories/UserRepository.php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * Chat ID bo'yicha foydalanuvchini topish yoki yaratish
     */
    public function checkOrCreate(string $chatId): array
    {
        $user = User::where('chat_id', $chatId)->first();

        if ($user) {
            return ['user' => $user, 'exists' => true];
        }

        $user = User::create([
            'chat_id' => $chatId,
            'step' => 'start',
            'language' => 'uz'
        ]);

        return ['user' => $user, 'exists' => false];
    }

    /**
     * Foydalanuvchini yangilash
     */
    public function update(string $chatId, array $data): ?User
    {
        $user = User::where('chat_id', $chatId)->first();

        if ($user) {
            $user->update($data);
            return $user->fresh();
        }

        return null;
    }

    /**
     * Qadamni yangilash
     */
    public function page(string $chatId, ?string $step = null): ?string
    {
        $user = User::where('chat_id', $chatId)->first();

        if (!$user) {
            return null;
        }

        if ($step !== null) {
            $user->update(['step' => $step]);
            return $step;
        }

        return $user->step;
    }

    /**
     * Tilni olish yoki o'rnatish
     */
    public function language(string $chatId, ?string $lang = null): ?string
    {
        $user = User::where('chat_id', $chatId)->first();

        if (!$user) {
            return null;
        }

        if ($lang !== null) {
            $user->update(['language' => $lang]);
            return $lang;
        }

        return $user->language;
    }

    /**
     * Telefon raqamni saqlash
     */
    public function phone(string $chatId, string $phone): ?User
    {
        return $this->update($chatId, ['phone' => $phone]);
    }

    /**
     * Konsultatsiya ID ni saqlash
     */
    public function consultation(string $chatId, ?int $consultationId = null): ?int
    {
        $user = User::where('chat_id', $chatId)->first();

        if (!$user) {
            return null;
        }

        if ($consultationId !== null) {
            $user->update(['consultation' => $consultationId]);
            return $consultationId;
        }

        return $user->consultation;
    }

    /**
     * Foydalanuvchini o'chirish (soft delete)
     */
    public function delete(string $chatId): bool
    {
        $user = User::where('chat_id', $chatId)->first();

        if ($user) {
            return $user->delete();
        }

        return false;
    }

    /**
     * Chat ID bo'yicha foydalanuvchini topish
     */
    public function findByChatId(string $chatId): ?User
    {
        return User::where('chat_id', $chatId)->first();
    }
}

// app/Repositories/TelegramTextRepository.php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;

class TelegramTextRepository
{
    private array $texts = [
        // O'zbek tilida
        'uz' => [
            'ask_phone_text' => '📱 Iltimos, telefon raqamingizni yuboring:',
            'ask_phone_button' => '📱 Telefon raqamni yuborish',
            'ask_correct_phone_text' => '❌ Noto\'g\'ri format. Qaytadan kiriting.',
            'main_page_text' => '🍞 <b>Tahsiin Non</b>\n\nNima qilmoqchisiz?',
            'consultation_button' => '💬 Maslahat',
            'help_button' => '❓ Yordam',
            'appeals_button' => '✍️ Murojaat',
            'history_of_appeals_button' => '📋 Tarix',
            'settings_button' => '⚙️ Sozlamalar',
            'contact_button' => '📞 Aloqa',
            'main_page_button' => '🏠 Bosh sahifa',
            'back_button' => '◀️ Ortga',
        ],
        // Rus tilida
        'ru' => [
            'ask_phone_text' => '📱 Пожалуйста, отправьте ваш номер:',
            'ask_phone_button' => '📱 Отправить номер',
            'ask_correct_phone_text' => '❌ Неверный формат. Введите снова.',
            'main_page_text' => '🍞 <b>Tahsiin Non</b>\n\nЧто хотите сделать?',
            'consultation_button' => '💬 Консультация',
            'help_button' => '❓ Помощь',
            'appeals_button' => '✍️ Обращение',
            'history_of_appeals_button' => '📋 История',
            'settings_button' => '⚙️ Настройки',
            'contact_button' => '📞 Контакт',
            'main_page_button' => '🏠 Главная',
            'back_button' => '◀️ Назад',
        ]
    ];

    /**
     * Matnni olish yoki yaratish
     */
    public function getOrCreate(string $key, string $lang = 'uz'): string
    {
        return $this->texts[$lang][$key] ?? $key;
    }

    /**
     * Kalit so'z orqali topish
     */
    public function getKeyword(string $text, string $lang = 'uz'): ?string
    {
        $texts = $this->texts[$lang] ?? [];

        $key = array_search($text, $texts);

        return $key ?: null;
    }

    /**
     * Matnni klaviatura bilan tekshirish
     */
    public function checkTextWithKeyboard(string $text): bool
    {
        foreach ($this->texts as $langTexts) {
            if (in_array($text, $langTexts)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Buyurtma qabul qilindi matni
     */
    public function successAcceptText(string $lang, int $orderId, string $date): string
    {
        if ($lang === 'uz') {
            return "✅ <b>Buyurtma qabul qilindi!</b>\n\n📦 Raqam: #{$orderId}\n📅 Vaqt: {$date}";
        }

        return "✅ <b>Заказ принят!</b>\n\n📦 Номер: #{$orderId}\n📅 Время: {$date}";
    }
}
