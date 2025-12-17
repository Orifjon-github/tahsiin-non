<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\TelegramTextRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $chat_id;
    private ?string $text;
    private Telegram $telegram;
    private UserRepository $userRepository;
    private TelegramTextRepository $textRepository;

    // Qadamlar
    const STEP_START = 'start';
    const STEP_PHONE = 'phone';
    const STEP_ADDRESS = 'address';
    const STEP_ADDRESS_METHOD = 'address_method'; // Yangi: Qolda yoki lokatsiya
    const STEP_CONFIRM_ADDRESS = 'confirm_address';
    const STEP_MAIN_MENU = 'main_menu';
    const STEP_SELECT_BREAD = 'select_bread';
    const STEP_SELECT_TIME = 'select_time';
    const STEP_CONFIRM_ORDER = 'confirm_order';

    // Tillar
    const LANG_UZ = '🇺🇿 O\'zbekcha';
    const LANG_RU = '🇷🇺 Русский';

    // Vaqt oraliq lari (6:00 - 10:00)
    const TIME_SLOTS = [
        '6:00-6:30' => '🌅 6:00-6:30',
        '6:30-7:00' => '🌅 6:30-7:00',
        '7:00-7:30' => '☀️ 7:00-7:30',
        '7:30-8:00' => '☀️ 7:30-8:00',
        '8:00-8:30' => '☀️ 8:00-8:30',
        '8:30-9:00' => '☀️ 8:30-9:00',
        '9:00-9:30' => '☀️ 9:00-9:30',
        '9:30-10:00' => '☀️ 9:30-10:00',
    ];

    // Default manzil (QR kodsiz)
    const DEFAULT_DISTRICT = 'Yashnabod tumani';
    const DEFAULT_MAHALLA = 'Xavas mahalla';

    // Telegram guruh ID
    const ADMIN_GROUP_ID = '-1003626670279';

    public function __construct(
        Telegram               $telegram,
        UserRepository         $userRepository,
        TelegramTextRepository $textRepository
    )
    {
        $this->telegram = $telegram;
        $this->chat_id = $telegram->ChatID();
        $this->text = $telegram->Text();
        $this->userRepository = $userRepository;
        $this->textRepository = $textRepository;
    }

    /**
     * Asosiy ishlov berish funksiyasi
     */
    public function start(): bool
    {
        try {
            // MUHIM: Guruh xabarlarini ignore qilish
            $chatType = $this->telegram->getData()['message']['chat']['type'] ?? 'private';

            if ($chatType !== 'private') {
                Log::info('Non-private chat message ignored', [
                    'chat_type' => $chatType,
                    'chat_id' => $this->chat_id
                ]);
                return false;
            }

            // Agar /start yoki QR kod orqali kelgan bo'lsa
            if (str_starts_with($this->text, '/start')) {
                $this->handleStart();
                return true;
            }

            $user = User::where('chat_id', $this->chat_id)->first();

            if (!$user) {
                $this->sendWelcome();
                return true;
            }

            $step = $user->step ?? self::STEP_START;

            switch ($step) {
                case self::STEP_START:
                    $this->handleLanguageSelection();
                    break;

                case self::STEP_PHONE:
                    $this->handlePhoneInput();
                    break;

                case self::STEP_ADDRESS:
                    $this->handleAddressInput();
                    break;

                case self::STEP_ADDRESS_METHOD:
                    $this->handleAddressMethod();
                    break;

                case self::STEP_CONFIRM_ADDRESS:
                    $this->handleAddressConfirmation();
                    break;

                case self::STEP_MAIN_MENU:
                    $this->handleMainMenu();
                    break;

                case self::STEP_SELECT_BREAD:
                    $this->handleBreadSelection();
                    break;

                case self::STEP_SELECT_TIME:
                    $this->handleTimeSelection();
                    break;

                case self::STEP_CONFIRM_ORDER:
                    $this->handleOrderConfirmation();
                    break;

                default:
                    $this->showMainMenu();
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Tahsiin Bot Error: ' . $e->getMessage());
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => '❌ Xatolik yuz berdi. Iltimos qaytadan urinib ko\'ring.',
            ]);
            return false;
        }
    }

    /**
     * /start buyrug'ini ishlov berish
     */
    private function handleStart(): void
    {
        // QR kod orqali kelgan bo'lsa: /start ref_12
        // 12 - uy raqami
        $payload = trim(str_replace('/start', '', $this->text));
        $params = explode('_', $payload);

        $user = User::firstOrCreate(
            ['chat_id' => $this->chat_id],
            [
                'step' => self::STEP_START,
                'language' => 'uz',
            ]
        );

        // Agar QR kod orqali kelgan bo'lsa (faqat uy raqami)
        if (count($params) >= 2 && $params[0] === 'ref') {
            $building = $params[1] ?? null;

            if ($building) {
                // Uy raqamini saqlash
                $user->update([
                    'building_number' => $building,
                    'temp_address' => self::DEFAULT_DISTRICT . ', ' . self::DEFAULT_MAHALLA . ', ' . $building . '-uy',
                    'from_qr' => true // QR kod orqali kelganini belgilash
                ]);

                $this->sendWelcomeWithBuilding($user);
                return;
            }
        }

        // Oddiy /start (QR kodsiz)
        $user->update(['from_qr' => false]);
        $this->sendWelcome();
    }

    /**
     * Xush kelibsiz xabari (QR kodsiz)
     */
    private function sendWelcome(): void
    {
        $text = "🍞 <b>Tahsiin Non</b>ga xush kelibsiz!\n\n";
        $text .= "Har kuni yangi pishgan issiq nonni eshigingizgacha yetkazib beramiz.\n\n";
        $text .= "🕐 Yetkazish vaqti: 6:00-10:00\n\n";
        $text .= "Iltimos, tilni tanlang:";

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton(self::LANG_UZ)],
            [$this->telegram->buildKeyboardButton(self::LANG_RU)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * QR kod orqali kelgan foydalanuvchiga xabar (uy raqami bilan)
     */
    private function sendWelcomeWithBuilding(User $user): void
    {
        $text = "🍞 <b>Tahsiin Non</b>ga xush kelibsiz!\n\n";
        $text .= "Siz QR kod orqali kirdingiz.\n\n";
        $text .= "📍 Manzil: <b>{$user->temp_address}</b>\n\n";
        $text .= "Iltimos, tilni tanlang:";

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton(self::LANG_UZ)],
            [$this->telegram->buildKeyboardButton(self::LANG_RU)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Til tanlash
     */
    private function handleLanguageSelection(): void
    {
        $lang = match ($this->text) {
            self::LANG_UZ => 'uz',
            self::LANG_RU => 'ru',
            default => null
        };

        if (!$lang) {
            $this->sendWelcome();
            return;
        }

        $user = User::where('chat_id', $this->chat_id)->first();
        $user->update([
            'language' => $lang,
            'step' => self::STEP_PHONE
        ]);

        $this->askPhone($user);
    }

    /**
     * Telefon raqam so'rash
     */
    private function askPhone(User $user): void
    {
        $text = $user->language === 'uz'
            ? "📱 Iltimos, telefon raqamingizni yuboring:\n\nTugmani bosing yoki +998 formatida yozing."
            : "📱 Пожалуйста, отправьте ваш номер телефона:\n\nНажмите кнопку или напишите в формате +998.";

        $buttonText = $user->language === 'uz' ? '📱 Telefon raqamni yuborish' : '📱 Отправить номер';

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton($buttonText, true)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Telefon raqamni qabul qilish
     */
    private function handlePhoneInput(): void
    {
        $phone = $this->extractPhone($this->text);

        if (!$phone) {
            $user = User::where('chat_id', $this->chat_id)->first();
            $text = $user->language === 'uz'
                ? "❌ Noto'g'ri format. Iltimos, to'g'ri telefon raqam kiriting.\n\nMasalan: +998901234567"
                : "❌ Неверный формат. Пожалуйста, введите правильный номер.\n\nНапример: +998901234567";

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text,
                'parse_mode' => 'html'
            ]);
            return;
        }

        $user = User::where('chat_id', $this->chat_id)->first();
        $user->update(['phone' => $phone]);

        // Agar QR kod orqali kelgan bo'lsa - faqat xonadon raqami so'rash
        if ($user->from_qr && $user->building_number) {
            $user->update(['step' => self::STEP_ADDRESS]);
            $this->askApartmentNumber($user);
        } else {
            // Aks holda to'liq manzil so'rash
            $user->update(['step' => self::STEP_ADDRESS]);
            $this->askFullAddress($user);
        }
    }

    /**
     * Telefon raqamni ajratib olish
     */
    private function extractPhone(?string $text): ?string
    {
        if (!$text) return null;

        // +998 formatini tekshirish
        $text = preg_replace('/[^\d+]/', '', $text);

        if (preg_match('/^\+?998\d{9}$/', $text)) {
            return '+' . ltrim($text, '+');
        }

        return null;
    }

    /**
     * Xonadon raqami so'rash (QR kod orqali kelganlarga)
     */
    private function askApartmentNumber(User $user): void
    {
        $text = $user->language === 'uz'
            ? "🏠 Iltimos, xonadon raqamingizni kiriting yoki boshqa manzilni tanlang:\n\n"
            . "📍 <b>{$user->temp_address}</b>"
            : "🏠 Пожалуйста, введите номер квартиры или выберите другой адрес:\n\n"
            . "📍 <b>{$user->temp_address}</b>";

        $otherAddressBtn = $user->language === 'uz' ? '📍 Boshqa manzil' : '📍 Другой адрес';

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton($otherAddressBtn)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * To'liq manzil so'rash (QR kodsiz kirganlar uchun)
     */
    private function askFullAddress(User $user): void
    {
        $text = $user->language === 'uz'
            ? "🏠 Iltimos, manzilni tanlang:"
            : "🏠 Пожалуйста, выберите способ ввода адреса:";

        $manualBtn = $user->language === 'uz' ? '✍️ Qo\'lda kiritish' : '✍️ Ввести вручную';
        $locationBtn = $user->language === 'uz' ? '📍 Lokatsiya yuborish' : '📍 Отправить локацию';

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton($locationBtn, false, true)],
            [$this->telegram->buildKeyboardButton($manualBtn)]
        ], false, true);

        $user->update(['step' => self::STEP_ADDRESS_METHOD]);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Manzil kiritish usulini tanlash
     */
    private function handleAddressMethod(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        $manualBtn = $user->language === 'uz' ? '✍️ Qo\'lda kiritish' : '✍️ Ввести вручную';
        $locationBtn = $user->language === 'uz' ? '📍 Lokatsiya yuborish' : '📍 Отправить локацию';

        // Lokatsiya yuborilgan
        if ($this->telegram->getUpdateType() === Telegram::LOCATION) {
            $location = $this->telegram->Location();

            // Texnik ishlar xabari
            $text = $user->language === 'uz'
                ? "🔧 <b>Texnik ishlar</b>\n\nHozircha lokatsiya orqali manzil aniqlash ishlamayapti.\n\nIltimos, manzilni qo'lda kiriting:"
                : "🔧 <b>Технические работы</b>\n\nПока определение адреса по локации не работает.\n\nПожалуйста, введите адрес вручную:";

            $keyboard = $this->telegram->buildKeyBoard([
                [$this->telegram->buildKeyboardButton($manualBtn)]
            ], false, true);

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'html'
            ]);

            // TODO: Kelajakda lokatsiya bilan ishlash logikasini qo'shish
            // $this->processLocation($location, $user);

            return;
        }

        // Qo'lda kiritish tanlangan
        if ($this->text === $manualBtn) {
            $text = $user->language === 'uz'
                ? "🏠 Manzilni kiriting:\n\nMasalan: <b>Sergeli tumani, 5-mavze, 12-uy, 45-xonadon</b>"
                : "🏠 Введите адрес:\n\nНапример: <b>Сергелийский район, 5-массив, дом 12, квартира 45</b>";

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text,
                'parse_mode' => 'html'
            ]);

            $user->update(['step' => self::STEP_ADDRESS]);
            return;
        }

        // Noto'g'ri tanlov
        $this->askFullAddress($user);
    }

    /**
     * Manzilni qabul qilish
     */
    private function handleAddressInput(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        $otherAddressBtn = $user->language === 'uz' ? '📍 Boshqa manzil' : '📍 Другой адрес';

        // Agar "Boshqa manzil" tanlangan bo'lsa
        if ($this->text === $otherAddressBtn) {
            $user->update([
                'building_number' => null,
                'temp_address' => null,
                'from_qr' => false
            ]);
            $this->askFullAddress($user);
            return;
        }

        // Agar QR kod orqali kelgan bo'lsa - faqat xonadon raqami
        if ($user->from_qr && $user->building_number) {
            // Faqat raqam kiritilgan
            if (preg_match('/^\d+$/', trim($this->text))) {
                $apartment = trim($this->text);
                $address = self::DEFAULT_DISTRICT . ', ' . self::DEFAULT_MAHALLA . ', '
                    . $user->building_number . '-uy, ' . $apartment . '-xonadon';

                $user->update([
                    'apartment_number' => $apartment,
                    'temp_address' => $address,
                    'step' => self::STEP_CONFIRM_ADDRESS
                ]);

                $this->askAddressConfirmation($user);
                return;
            }

            // Noto'g'ri format
            $text = $user->language === 'uz'
                ? "❌ Faqat xonadon raqamini kiriting.\n\nMasalan: <b>45</b>"
                : "❌ Введите только номер квартиры.\n\nНапример: <b>45</b>";

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text,
                'parse_mode' => 'html'
            ]);
            return;
        }

        // Qo'lda to'liq manzil kiritish
        $address = trim($this->text);

        if (strlen($address) < 10) {
            $text = $user->language === 'uz'
                ? "❌ Manzil juda qisqa. Iltimos, to'liq manzilni kiriting.\n\nMasalan: <b>Sergeli tumani, 5-mavze, 12-uy, 45-xonadon</b>"
                : "❌ Адрес слишком короткий. Пожалуйста, введите полный адрес.\n\nНапример: <b>Сергелийский район, 5-массив, дом 12, квартира 45</b>";

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text,
                'parse_mode' => 'html'
            ]);
            return;
        }

        $user->update([
            'temp_address' => $address,
            'step' => self::STEP_CONFIRM_ADDRESS
        ]);

        $this->askAddressConfirmation($user);
    }

    /**
     * Manzilni tasdiqlash so'rash
     */
    private function askAddressConfirmation(User $user): void
    {
        $text = $user->language === 'uz'
            ? "📍 Sizning manzilingiz:\n\n<b>{$user->temp_address}</b>\n\nTo'g'rimi?"
            : "📍 Ваш адрес:\n\n<b>{$user->temp_address}</b>\n\nВерно?";

        $yesBtn = $user->language === 'uz' ? '✅ Ha, to\'g\'ri' : '✅ Да, верно';
        $noBtn = $user->language === 'uz' ? '❌ Yo\'q, o\'zgartirish' : '❌ Нет, изменить';

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton($yesBtn)],
            [$this->telegram->buildKeyboardButton($noBtn)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Manzil tasdiqlash
     */
    private function handleAddressConfirmation(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        $isConfirm = ($user->language === 'uz' && $this->text === '✅ Ha, to\'g\'ri') ||
            ($user->language === 'ru' && $this->text === '✅ Да, верно');

        if ($isConfirm) {
            $user->update([
                'address' => $user->temp_address,
                'step' => self::STEP_MAIN_MENU
            ]);
            $this->showMainMenu();
        } else {
            // Manzilni qayta kiritish
            if ($user->from_qr && $user->building_number) {
                $user->update(['step' => self::STEP_ADDRESS]);
                $this->askApartmentNumber($user);
            } else {
                $user->update(['step' => self::STEP_ADDRESS_METHOD]);
                $this->askFullAddress($user);
            }
        }
    }

    /**
     * Asosiy menyu
     */
    private function showMainMenu(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        $text = $user->language === 'uz'
            ? "🍞 <b>Tahsiin Non</b>\n\nNima qilmoqchisiz?"
            : "🍞 <b>Tahsiin Non</b>\n\nЧто вы хотите сделать?";

        $orderBtn = $user->language === 'uz' ? '🛒 Buyurtma berish' : '🛒 Сделать заказ';
        $historyBtn = $user->language === 'uz' ? '📋 Buyurtmalarim' : '📋 Мои заказы';
        $settingsBtn = $user->language === 'uz' ? '⚙️ Sozlamalar' : '⚙️ Настройки';

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton($orderBtn)],
            [$this->telegram->buildKeyboardButton($historyBtn), $this->telegram->buildKeyboardButton($settingsBtn)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Asosiy menyudan tanlov
     */
    private function handleMainMenu(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        if (
            ($user->language === 'uz' && $this->text === '🛒 Buyurtma berish') ||
            ($user->language === 'ru' && $this->text === '🛒 Сделать заказ')
        ) {
            $user->update(['step' => self::STEP_SELECT_BREAD]);
            $this->askBreadQuantity($user);
        } elseif (
            ($user->language === 'uz' && $this->text === '📋 Buyurtmalarim') ||
            ($user->language === 'ru' && $this->text === '📋 Мои заказы')
        ) {
            $this->showOrderHistory($user);
        } elseif (
            ($user->language === 'uz' && $this->text === '⚙️ Sozlamalar') ||
            ($user->language === 'ru' && $this->text === '⚙️ Настройки')
        ) {
            $this->showSettings($user);
        } elseif (
            ($user->language === 'uz' && $this->text === '📍 Manzilni o\'zgartirish') ||
            ($user->language === 'ru' && $this->text === '📍 Изменить адрес')
        ) {
            $this->askFullAddress($user);
        } else {
            $this->showMainMenu();
        }
    }

    /**
     * Non sonini so'rash
     */
    private function askBreadQuantity(User $user): void
    {
        $text = $user->language === 'uz'
            ? "🍞 Nechta non buyurtma qilmoqchisiz?\n\n1 dona non: <b>3,500 so'm</b>"
            : "🍞 Сколько хлебов вы хотите заказать?\n\n1 хлеб: <b>3,500 сум</b>";

        $buttons = [];
        $row = [];
        for ($i = 1; $i <= 10; $i++) {
            $row[] = $this->telegram->buildKeyboardButton((string)$i);
            if ($i % 5 === 0) {
                $buttons[] = $row;
                $row = [];
            }
        }

        $cancelBtn = $user->language === 'uz' ? '❌ Bekor qilish' : '❌ Отмена';
        $buttons[] = [$this->telegram->buildKeyboardButton($cancelBtn)];

        $keyboard = $this->telegram->buildKeyBoard($buttons, false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Non sonini qabul qilish
     */
    private function handleBreadSelection(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        // Bekor qilish
        if (
            ($user->language === 'uz' && $this->text === '❌ Bekor qilish') ||
            ($user->language === 'ru' && $this->text === '❌ Отмена')
        ) {
            $user->update(['step' => self::STEP_MAIN_MENU]);
            $this->showMainMenu();
            return;
        }

        $quantity = (int)$this->text;

        if ($quantity < 1 || $quantity > 10) {
            $text = $user->language === 'uz'
                ? "❌1 dan 10 gacha son kiriting."
                : "❌ Введите число от 1 до 10.";

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text
            ]);
            return;
        }

        // Buyurtma yaratish yoki yangilash
        Order::updateOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'pending'
            ],
            [
                'quantity' => $quantity,
                'price_per_item' => 3500,
                'total_price' => $quantity * 3500
            ]
        );

        $user->update(['step' => self::STEP_SELECT_TIME]);
        $this->askDeliveryTime($user);
    }

    /**
     * Yetkazish vaqtini so'rash
     */
    private function askDeliveryTime(User $user): void
    {
        $text = $user->language === 'uz'
            ? "🕐 Qaysi vaqt oralig'ida yetkazib berish kerak?\n\n<b>Ertaga</b> ertalab:"
            : "🕐 В какое время доставить?\n\n<b>Завтра</b> утром:";

        $buttons = [];
        $row = [];
        $count = 0;

        foreach (self::TIME_SLOTS as $key => $label) {
            $row[] = $this->telegram->buildKeyboardButton($label);
            $count++;

            if ($count % 2 === 0) {
                $buttons[] = $row;
                $row = [];
            }
        }

        if (!empty($row)) {
            $buttons[] = $row;
        }

        $cancelBtn = $user->language === 'uz' ? '❌ Bekor qilish' : '❌ Отмена';
        $buttons[] = [$this->telegram->buildKeyboardButton($cancelBtn)];

        $keyboard = $this->telegram->buildKeyBoard($buttons, false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Vaqtni qabul qilish
     */
    private function handleTimeSelection(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        // Bekor qilish
        if (
            ($user->language === 'uz' && $this->text === '❌ Bekor qilish') ||
            ($user->language === 'ru' && $this->text === '❌ Отмена')
        ) {
            $user->update(['step' => self::STEP_MAIN_MENU]);
            $this->showMainMenu();
            return;
        }

        // Vaqt topish
        $selectedTime = null;
        foreach (self::TIME_SLOTS as $key => $label) {
            if ($this->text === $label) {
                $selectedTime = $key;
                break;
            }
        }

        if (!$selectedTime) {
            $this->askDeliveryTime($user);
            return;
        }

        // Buyurtmaga vaqt qo'shish
        $order = Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$order) {
            $this->showMainMenu();
            return;
        }

        $order->update([
            'delivery_time_slot' => $selectedTime,
            'delivery_date' => now()->addDay()->format('Y-m-d')
        ]);

        $user->update(['step' => self::STEP_CONFIRM_ORDER]);
        $this->showOrderConfirmation($user, $order);
    }

    /**
     * Buyurtmani tasdiqlashdan oldin ko'rsatish
     */
    private function showOrderConfirmation(User $user, Order $order): void
    {
        $deliveryDate = \Carbon\Carbon::parse($order->delivery_date)->locale($user->language === 'uz' ? 'uz' : 'ru')->isoFormat('D MMMM');

        $text = $user->language === 'uz'
            ? "✅ <b>Buyurtmangizni tasdiqlang</b>\n\n"
            . "🍞 Non: <b>{$order->quantity} dona</b>\n"
            . "💰 Summa: <b>" . number_format($order->total_price, 0, '.', ' ') . " so'm</b>\n"
            . "📍 Manzil: <b>{$user->address}</b>\n"
            . "🕐 Vaqt: <b>{$order->delivery_time_slot}</b>\n"
            . "📅 Sana: <b>{$deliveryDate}</b>\n\n"
            . "To'lov: <b>Naqd pul (yetkazishda)</b>"
            : "✅ <b>Подтвердите ваш заказ</b>\n\n"
            . "🍞 Хлеб: <b>{$order->quantity} шт</b>\n"
            . "💰 Сумма: <b>" . number_format($order->total_price, 0, '.', ' ') . " сум</b>\n"
            . "📍 Адрес: <b>{$user->address}</b>\n"
            . "🕐 Время: <b>{$order->delivery_time_slot}</b>\n"
            . "📅 Дата: <b>{$deliveryDate}</b>\n\n"
            . "Оплата: <b>Наличными (при доставке)</b>";

        $confirmBtn = $user->language === 'uz' ? '✅ Tasdiqlash' : '✅ Подтвердить';
        $cancelBtn = $user->language === 'uz' ? '❌ Bekor qilish' : '❌ Отмена';

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton($confirmBtn)],
            [$this->telegram->buildKeyboardButton($cancelBtn)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Buyurtmani tasdiqlash
     */
    private function handleOrderConfirmation(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();

        $isConfirm = ($user->language === 'uz' && $this->text === '✅ Tasdiqlash') ||
            ($user->language === 'ru' && $this->text === '✅ Подтвердить');

        if ($isConfirm) {
            $order = Order::where('user_id', $user->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$order) {
                $this->showMainMenu();
                return;
            }

            // Buyurtma raqamini generatsiya qilish
            $orderNumber = 'TN-' . strtoupper(substr(uniqid(), -8));

            $order->update([
                'status' => 'confirmed',
                'order_number' => $orderNumber,
                'confirmed_at' => now()
            ]);

            // Foydalanuvchiga xabar
            $this->sendOrderSuccess($user, $order);

            // Admin guruhga xabar
            $this->sendToAdminGroup($user, $order);

            // Asosiy menyuga qaytish
            $user->update(['step' => self::STEP_MAIN_MENU]);
            $this->showMainMenu();
        } else {
            $user->update(['step' => self::STEP_MAIN_MENU]);
            $this->showMainMenu();
        }
    }

    /**
     * Buyurtma muvaffaqiyatli qabul qilinganligi haqida xabar
     */
    private function sendOrderSuccess(User $user, Order $order): void
    {
        $deliveryDate = \Carbon\Carbon::parse($order->delivery_date)->locale($user->language === 'uz' ? 'uz' : 'ru')->isoFormat('D MMMM');

        $text = $user->language === 'uz'
            ? "🎉 <b>Buyurtma qabul qilindi!</b>\n\n"
            . "📦 Buyurtma raqami: <b>#{$order->order_number}</b>\n"
            . "🍞 Non: <b>{$order->quantity} dona</b>\n"
            . "💰 Summa: <b>" . number_format($order->total_price, 0, '.', ' ') . " so'm</b>\n"
            . "📅 Sana: <b>{$deliveryDate}</b>\n"
            . "🕐 Vaqt: <b>{$order->delivery_time_slot}</b>\n\n"
            . "📱 Agar savollaringiz bo'lsa, @tahsiin_support ga murojaat qiling.\n\n"
            . "Ertaga ko'rishguncha! 🌅"
            : "🎉 <b>Заказ принят!</b>\n\n"
            . "📦 Номер заказа: <b>#{$order->order_number}</b>\n"
            . "🍞 Хлеб: <b>{$order->quantity} шт</b>\n"
            . "💰 Сумма: <b>" . number_format($order->total_price, 0, '.', ' ') . " сум</b>\n"
            . "📅 Дата: <b>{$deliveryDate}</b>\n"
            . "🕐 Время: <b>{$order->delivery_time_slot}</b>\n\n"
            . "📱 Если есть вопросы, обращайтесь @tahsiin_support.\n\n"
            . "До завтра! 🌅";

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Admin guruhga buyurtma haqida xabar yuborish
     */
    private function sendToAdminGroup(User $user, Order $order): void
    {
        $deliveryDate = \Carbon\Carbon::parse($order->delivery_date)->format('d.m.Y');

        $text = "🔔 <b>YANGI BUYURTMA</b>\n\n";
        $text .= "📦 Raqam: <b>#{$order->order_number}</b>\n";
        $text .= "👤 Mijoz: {$user->first_name} {$user->last_name}\n";
        $text .= "📱 Telefon: <b>{$user->phone}</b>\n";
        $text .= "📍 Manzil: <b>{$user->address}</b>\n\n";
        $text .= "🍞 Non: <b>{$order->quantity} dona</b>\n";
        $text .= "💰 Summa: <b>" . number_format($order->total_price, 0, '.', ' ') . " so'm</b>\n";
        $text .= "📅 Sana: <b>{$deliveryDate}</b>\n";
        $text .= "🕐 Vaqt: <b>{$order->delivery_time_slot}</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "⏰ <i>Buyurtma vaqti: " . now()->format('H:i') . "</i>";

        // Inline keyboard - Done/Fail
        $keyboard = $this->telegram->buildInlineKeyBoard([
            [
                $this->telegram->buildInlineKeyboardButton('✅ Bajarildi', '', "order_done_{$order->id}"),
                $this->telegram->buildInlineKeyboardButton('❌ Bekor', '', "order_fail_{$order->id}")
            ]
        ]);

        $this->telegram->sendMessage([
            'chat_id' => self::ADMIN_GROUP_ID,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Buyurtmalar tarixini ko'rsatish
     */
    private function showOrderHistory(User $user): void
    {
        $orders = Order::where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) {
            $text = $user->language === 'uz'
                ? "📋 Hozircha buyurtmalaringiz yo'q."
                : "📋 У вас пока нет заказов.";

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text
            ]);
            return;
        }

        foreach ($orders as $order) {
            $status = match ($order->status) {
                'confirmed' => $user->language === 'uz' ? '⏳ Tayyorlanmoqda' : '⏳ Готовится',
                'completed' => $user->language === 'uz' ? '✅ Yetkazildi' : '✅ Доставлено',
                'cancelled' => $user->language === 'uz' ? '❌ Bekor qilindi' : '❌ Отменён',
                default => '❓'
            };

            $date = \Carbon\Carbon::parse($order->delivery_date)->format('d.m.Y');

            $text = $user->language === 'uz'
                ? "📦 <b>#{$order->order_number}</b>\n"
                . "🍞 {$order->quantity} dona\n"
                . "💰 " . number_format($order->total_price, 0, '.', ' ') . " so'm\n"
                . "📅 {$date} • {$order->delivery_time_slot}\n"
                . "📊 Holat: {$status}"
                : "📦 <b>#{$order->order_number}</b>\n"
                . "🍞 {$order->quantity} шт\n"
                . "💰 " . number_format($order->total_price, 0, '.', ' ') . " сум\n"
                . "📅 {$date} • {$order->delivery_time_slot}\n"
                . "📊 Статус: {$status}";

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $text,
                'parse_mode' => 'html'
            ]);
        }
    }

    /**
     * Sozlamalar
     */
    private function showSettings(User $user): void
    {
        $text = $user->language === 'uz'
            ? "⚙️ <b>Sozlamalar</b>\n\n"
            . "👤 {$user->first_name} {$user->last_name}\n"
            . "📱 {$user->phone}\n"
            . "📍 {$user->address}\n"
            . "🌐 Til: O'zbekcha"
            : "⚙️ <b>Настройки</b>\n\n"
            . "👤 {$user->first_name} {$user->last_name}\n"
            . "📱 {$user->phone}\n"
            . "📍 {$user->address}\n"
            . "🌐 Язык: Русский";

        $changeAddressBtn = $user->language === 'uz' ? '📍 Manzilni o\'zgartirish' : '📍 Изменить адрес';
        $changeLangBtn = $user->language === 'uz' ? '🌐 Tilni o\'zgartirish' : '🌐 Изменить язык';
        $backBtn = $user->language === 'uz' ? '◀️ Ortga' : '◀️ Назад';

        $keyboard = $this->telegram->buildKeyBoard([
            [$this->telegram->buildKeyboardButton($changeAddressBtn)],
            [$this->telegram->buildKeyboardButton($changeLangBtn)],
            [$this->telegram->buildKeyboardButton($backBtn)]
        ], false, true);

        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Callback query (inline tugmalar)
     */
    public function handleCallbackQuery(): void
    {
        $callbackQuery = $this->telegram->Callback_Query();
        if (!$callbackQuery) return;

        $data = $callbackQuery['data'];
        $messageId = $callbackQuery['message']['message_id'];
        $chatId = $callbackQuery['message']['chat']['id'];

        // order_done_123 yoki order_fail_123
        if (str_starts_with($data, 'order_done_')) {
            $orderId = str_replace('order_done_', '', $data);
            $this->completeOrder($orderId, $messageId, $chatId);
        } elseif (str_starts_with($data, 'order_fail_')) {
            $orderId = str_replace('order_fail_', '', $data);
            $this->cancelOrder($orderId, $messageId, $chatId);
        }

        // Callback javob berish
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery['id']
        ]);
    }

    /**
     * Buyurtmani bajarilgan deb belgilash
     */
    private function completeOrder(int $orderId, int $messageId, string $chatId): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $order->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        // Xabarni yangilash
        $text = $this->telegram->Callback_Message()['text'];
        $text .= "\n\n✅ <b>BAJARILDI</b>\n⏰ " . now()->format('H:i d.m.Y');

        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'html'
        ]);

        // Mijozga xabar
        $user = $order->user;
        $clientText = $user->language === 'uz'
            ? "✅ <b>Buyurtma yetkazildi!</b>\n\n📦 #{$order->order_number}\n\nRahmat! Yana buyurtma bering 🍞"
            : "✅ <b>Заказ доставлен!</b>\n\n📦 #{$order->order_number}\n\nСпасибо! Заказывайте снова 🍞";

        $this->telegram->sendMessage([
            'chat_id' => $user->chat_id,
            'text' => $clientText,
            'parse_mode' => 'html'
        ]);
    }

    /**
     * Buyurtmani bekor qilish
     */
    private function cancelOrder(int $orderId, int $messageId, string $chatId): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        // Xabarni yangilash
        $text = $this->telegram->Callback_Message()['text'];
        $text .= "\n\n❌ <b>BEKOR QILINDI</b>\n⏰ " . now()->format('H:i d.m.Y');

        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'html'
        ]);

        // Mijozga xabar
        $user = $order->user;
        $clientText = $user->language === 'uz'
            ? "❌ Afsuski, buyurtmangiz bajarilmadi.\n\n📦 #{$order->order_number}\n\nYangi buyurtma berishingiz mumkin."
            : "❌ К сожалению, ваш заказ не выполнен.\n\n📦 #{$order->order_number}\n\nВы можете сделать новый заказ.";

        $this->telegram->sendMessage([
            'chat_id' => $user->chat_id,
            'text' => $clientText,
            'parse_mode' => 'html'
        ]);
    }
}
