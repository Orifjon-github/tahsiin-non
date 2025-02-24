<?php

namespace App\Services;


use App\Helpers\MainHelper;
use App\Helpers\Response;
use App\Helpers\TelegramHelper;
use App\Models\AppealType;
use App\Models\SupplierUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SupplierService
{
    use Response;
    private string $chat_id;
    private string|null $text;
    private Telegram $telegram;

    public function __construct(Telegram $telegram)
    {
        $this->telegram = $telegram;
        $this->telegram->bot_token = env('TELEGRAM_BOT_TOKEN');
    }

    public function sendPaymentInfo($supplier_id, $utid): JsonResponse
    {
        $users = SupplierUser::where('supplier_id', $supplier_id)->get();
        foreach ($users as $user) {
            $message = MainHelper::makeMessage($utid);
            $this->telegram->sendMessage(['chat_id' => $user->chat_id, 'text' => $message, 'parse_mode' => 'html']);
        }
        return $this->success((object)[]);
    }

    public function start(): bool
    {
        $this->chat_id = $this->telegram->ChatID();
        $this->text = $this->telegram->Text();
        $this->send($this->chat_id, 'Welcome');
        return 'OK';
        if ($this->text == '/start') {
            $this->handleRegistration();
        } else {
            switch ($this->userRepository->page($this->chat_id)) {
                case TelegramHelper::START_STEP:
                    switch ($this->text) {
                        case TelegramHelper::UZBEK_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'uz');
                            $this->askPhone();
                            break;
                        case TelegramHelper::RUSSIAN_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'ru');
                            $this->askPhone();
                            break;
                        case TelegramHelper::ENGLISH_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'en');
                            $this->askPhone();
                            break;
                        default:
                            $this->chooseLanguage();
                            break;
                    }
                    break;
                case TelegramHelper::PHONE_STEP:
                    if ($phone = TelegramHelper::checkPhone($this->text)) {
                        $this->userRepository->phone($this->chat_id, $phone);
                        $this->showMainPage();
                    } else {
                        $this->askCorrectPhone();
                    }
                    break;
                case TelegramHelper::MAIN_PAGE_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'settings_button':
                            $this->showSettings();
                            break;
                        case 'contact_button':
                            $this->showContact();
                            break;
                        case 'help_button':
                            $this->showHelp();
                            break;
                        case 'appeals_button':
                            $this->showAppeals();
                            break;
                        case 'consultation_button':
                            $this->showConsultation();
                            break;
                        case 'history_of_appeals_button':
                            $this->historyAppeals();
                            break;
                        default:
                            $this->showMainPage();
                            break;
                    }
                    break;
                case TelegramHelper::SETTINGS_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'change_language_button':
                            $this->chooseLanguage(true);
                            break;
                        case 'delete_account_button':
                            $this->deleteAccount();
                            break;
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        default:
                            $this->showSettings();
                            break;
                    }
                    break;
                case TelegramHelper::DELETE_ACCOUNT_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'confirm_delete_account_button':
                            $this->confirmDeleteAccount();
                            break;
                        case 'cancel_delete_account_button':
                            $this->cancelDeleteAccount();
                            break;
                        default:
                            $this->deleteAccount();
                            break;

                    }
                    break;
                case TelegramHelper::CHANGE_LANG_STEP:
                    switch ($this->text) {
                        case TelegramHelper::UZBEK_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'uz');
                            break;
                        case TelegramHelper::RUSSIAN_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'ru');
                            break;
                        case TelegramHelper::ENGLISH_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'en');
                            break;
                        default:
                            $this->chooseLanguage();
                            break;
                    }
                    $this->successChangeLang();
                    break;
                case TelegramHelper::APPEALS_STEP:
                    $lang = $this->userRepository->language($this->chat_id);
                    $attr = ($lang == 'uz') ? 'name' : "name_$lang";
                    $appeal = $this->appealRepository->getAppealType($attr, $this->text);
                    if ($appeal) {
                        $this->appealRepository->updateOrCreateAppeal($this->chat_id, ['appeal_type_id' => $appeal->id]);
                        $this->askAppealTitle();
                    } elseif ($this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id)) == 'main_page_button') {
                        $this->showMainPage();
                    } else {
                        $this->showAppeals();
                    }
                    break;
                case TelegramHelper::CONSULTATION:
                    $lang = $this->userRepository->language($this->chat_id);
                    $attr = ($lang == 'uz') ? 'name' : "name_$lang";
                    $consultation = $this->consultation::findConsultation($attr, $this->text);
                    if ($consultation) {
                        $children = $this->consultation::getChildrenOrFalse($consultation->id);
                        if ($children) $this->userRepository->consultation($this->chat_id, $consultation->id);
                        $this->showConsultationInfoOrChildren($consultation);
                    } else {
                        $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                        switch ($keyword) {
                            case 'main_page_button':
                                $this->showMainPage();
                                break;
                            case 'back_button':
                                $consultation = $this->consultation::find($this->userRepository->consultation($this->chat_id));
                                $parent = $consultation->parent;
                                if ($parent) {
                                    $this->userRepository->consultation($this->chat_id, $parent->id);
                                    $oldParent = $parent->parent;
                                    if ($oldParent) {
                                        $this->showConsultationInfoOrChildren($oldParent);
                                    } else {
                                        $this->showConsultationInfoOrChildren($parent);
                                    }
                                } else {
                                    $this->showConsultation();
                                }
                                break;
                            default:
                                $this->showConsultation();
                        }
                    }
                    break;
                case TelegramHelper::ASK_APPEAL_TITLE:
                    if ($this->text == 'back_button') {
                        $this->back(TelegramHelper::APPEALS_STEP, 'showAppeals');
                    } elseif ($this->text == 'main_page_button') {
                        $this->showMainPage();
                    } else {
                        $this->appealRepository->updateOrCreateAppeal($this->chat_id, ['title' => $this->text]);
                        $this->askAppealDescription();
                    }
                    break;
                case TelegramHelper::ASK_APPEAL_DESCRIPTION:
                    if ($this->text == 'back_button') {
                        $this->back(TelegramHelper::APPEALS_STEP, 'askAppealTitle');
                    } elseif ($this->text == 'main_page_button') {
                        $this->showMainPage();
                    } else {
                        $chat = $this->appealRepository->updateOrCreateAppeal($this->chat_id, ['message' => $this->text, 'status' => 'ready']);
                        $this->successAcceptAppeal($chat);
                    }
                    break;
                case TelegramHelper::ACTIVE_CHAT:
                    $message = $this->appealRepository->appealMessage($this->chat_id, $this->text);
                    if (!$message) {
                        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'active_chat_not_found_text']);
                        $this->showAppeals();
                    }
                    break;
                case TelegramHelper::HELP_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        case 'help_capability_button':
                            $this->showHelp(true);
                            break;
                        case 'help_instructions_button':
                            $this->showHelp(false, true);
                            break;
                        default:
                            $this->showHelp();
                    }
            }
        }
        return true;
    }

    private function chooseLanguage($is_setting = false): void
    {
        $text = TelegramHelper::CHOOSE_LANGUAGE_TEXT;
        if ($is_setting) $this->userRepository->page($this->chat_id, TelegramHelper::CHANGE_LANG_STEP);
        $option = [[$this->telegram->buildKeyboardButton(TelegramHelper::UZBEK_LANGUAGE)], [$this->telegram->buildKeyboardButton(TelegramHelper::RUSSIAN_LANGUAGE), $this->telegram->buildKeyboardButton(TelegramHelper::ENGLISH_LANGUAGE)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function askPhone(): void
    {
        $text = $this->textRepository->getOrCreate('ask_phone_text', $this->userRepository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('ask_phone_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::PHONE_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButton, true)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function askCorrectPhone(): void
    {
        $text = $this->textRepository->getOrCreate('ask_correct_phone_text', $this->userRepository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('ask_phone_button', $this->userRepository->language($this->chat_id));
        $option = [[$this->telegram->buildKeyboardButton($textButton, true)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function showMainPage(): void
    {
        $text = $this->textRepository->getOrCreate('main_page_text', $this->userRepository->language($this->chat_id));
        $textButton_1 = $this->textRepository->getOrCreate('consultation_button', $this->userRepository->language($this->chat_id));
        $textButton_2 = $this->textRepository->getOrCreate('help_button', $this->userRepository->language($this->chat_id));
        $textButton_3 = $this->textRepository->getOrCreate('appeals_button', $this->userRepository->language($this->chat_id));
        $textButton_4 = $this->textRepository->getOrCreate('history_of_appeals_button', $this->userRepository->language($this->chat_id));
        $textButton_5 = $this->textRepository->getOrCreate('settings_button', $this->userRepository->language($this->chat_id));
        $textButton_6 = $this->textRepository->getOrCreate('contact_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::MAIN_PAGE_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButton_1), $this->telegram->buildKeyboardButton($textButton_3), $this->telegram->buildKeyboardButton($textButton_5)], [$this->telegram->buildKeyboardButton($textButton_2), $this->telegram->buildKeyboardButton($textButton_4), $this->telegram->buildKeyboardButton($textButton_6)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function deleteAccount(): void
    {
        $text = $this->textRepository->getOrCreate('confirm_delete_account_text', $this->userRepository->language($this->chat_id));
        $textConfirm = $this->textRepository->getOrCreate('confirm_delete_account_button', $this->userRepository->language($this->chat_id));
        $textCancel = $this->textRepository->getOrCreate('cancel_delete_account_button', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::DELETE_ACCOUNT_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textCancel), $this->telegram->buildKeyboardButton($textConfirm)], [$this->telegram->buildKeyboardButton($backButton)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function cancelDeleteAccount(): void
    {
        $text = $this->textRepository->getOrCreate('cancel_delete_account_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function confirmDeleteAccount(): void
    {
        $this->userRepository->delete($this->chat_id);
        $text = $this->textRepository->getOrCreate('success_delete_account_text', $this->userRepository->language($this->chat_id));
        $textRegister = $this->textRepository->getOrCreate('register_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::START_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textRegister)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function showContact(): void
    {
        $text = $this->textRepository->getOrCreate('contact_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
    }

    public function showHelp($capability = false, $instruction = false): void
    {
        $this->userRepository->page($this->chat_id, TelegramHelper::HELP_STEP);
        $text = $capability ? $this->textRepository->getOrCreate('help_capability_text', $this->userRepository->language($this->chat_id)) : $this->textRepository->getOrCreate('help_text', $this->userRepository->language($this->chat_id));
        $helpButton_1 = $this->textRepository->getOrCreate('help_capability_button', $this->userRepository->language($this->chat_id));
        $helpButton_2 = $this->textRepository->getOrCreate('help_instructions_button', $this->userRepository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $option = [[$this->telegram->buildKeyboardButton($helpButton_1), $this->telegram->buildKeyboardButton($helpButton_2)], [$this->telegram->buildKeyboardButton($textButtonMain)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        if ($instruction) {
            $documentPath = env('APP_URL') . '/storage/instruction.pdf';
            $caption = $this->textRepository->getOrCreate('help_instruction_caption_text', $this->userRepository->language($this->chat_id));
            $this->telegram->sendDocument(['chat_id' => $this->chat_id, 'document' => $documentPath, 'caption' => $caption, 'parse_mode' => 'html']);
        } else {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
        }
    }

    public function showAppeals(): void
    {
        $cacheKey = "appeals_{$this->chat_id}";

        $text = $this->textRepository->getOrCreate('choose_appeals_text', $this->userRepository->language($this->chat_id));

        $cacheData = Cache::remember($cacheKey, 86400, function () {
            $appeals = AppealType::all();
            $keyboard = $this->makeDynamicKeyboards($appeals, false, true);
            return ['appeals' => $appeals, 'keyboard' => $keyboard];
        });

        $this->userRepository->page($this->chat_id, TelegramHelper::APPEALS_STEP);
        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $cacheData['keyboard'],
            'parse_mode' => 'html'
        ]);
    }

    public function showConsultation(): void
    {
        $text = $this->textRepository->getOrCreate('choose_consultation_text', $this->userRepository->language($this->chat_id));
        $parentConsultations = $this->consultation::getTopParents();
        $this->userRepository->page($this->chat_id, TelegramHelper::CONSULTATION);
        $keyboard = $this->makeDynamicKeyboards($parentConsultations);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function makeDynamicKeyboards($objects, $backButton = false, $onetime = false): bool|string
    {
        $option = [];
        $temp = [];
        $lang = $this->userRepository->language($this->chat_id);
        foreach ($objects as $object) {
            $buttonText = TelegramHelper::getValue($object, $lang);
            $temp[] = $this->telegram->buildKeyboardButton($buttonText);
            if (count($temp) === 3) {
                $option[] = $temp;
                $temp = [];
            }
        }

        if (!empty($temp)) {
            $option[] = $temp;
        }
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $textButtonBack = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $option[] = $backButton ? [$this->telegram->buildKeyboardButton($textButtonMain), $this->telegram->buildKeyboardButton($textButtonBack)] : [$this->telegram->buildKeyboardButton($textButtonMain)];

        return $this->telegram->buildKeyBoard($option, $onetime, true);
    }

    public function showConsultationInfoOrChildren($consultation): void
    {
        $children = $this->consultation::getChildrenOrFalse($consultation->id);
        if ($children) {
            $text = $this->textRepository->getOrCreate('choose_consultation_text', $this->userRepository->language($this->chat_id));
            $keyboard = $this->makeDynamicKeyboards($children, true);
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
        } else {
            $language = $this->userRepository->language($this->chat_id);
            $infoAttr = $language == 'uz' ? 'info' : 'info_' . $language;
            $text = $consultation->$infoAttr;
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        }
    }

    public function askAppealTitle(): void
    {
        $text = $this->textRepository->getOrCreate('ask_appeal_title_text', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_APPEAL_TITLE);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
    }

    public function askAppealDescription(): void
    {
        $text = $this->textRepository->getOrCreate('ask_appeal_description_text', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_APPEAL_DESCRIPTION);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
    }

    public function successAcceptAppeal($chat): void
    {
        $text = $this->textRepository->successAcceptText($this->userRepository->language($this->chat_id), $chat->id, $chat->updated_at);
        $this->userRepository->page($this->chat_id, TelegramHelper::ACTIVE_CHAT);
//        $textDeclineButton = $this->textRepository->getOrCreate('decline_appeal_button', $this->userRepository->language($this->chat_id));
//        $option = [[$this->telegram->buildKeyboardButton($textDeclineButton)]];
//        $keyboard = $this->telegram->buildKeyBoard($option, true, true); // decline from client side
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function showSettings(): void
    {
        $text = $this->textRepository->getOrCreate('main_page_text', $this->userRepository->language($this->chat_id));
        $textButtonChangeLang = $this->textRepository->getOrCreate('change_language_button', $this->userRepository->language($this->chat_id));
        $textButtonDelete = $this->textRepository->getOrCreate('delete_account_button', $this->userRepository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::SETTINGS_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButtonChangeLang), $this->telegram->buildKeyboardButton($textButtonDelete)], [$this->telegram->buildKeyboardButton($textButtonMain)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function successChangeLang(): void
    {
        $text = $this->textRepository->getOrCreate('success_change_language', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function historyAppeals(): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();
        $appeals = $user->chats;

        if (count($appeals) > 0) {
            foreach ($appeals as $appeal) {
                $status = TelegramHelper::statuses($appeal->status);
                $ID = $this->textRepository->getOrCreate('appeals_history_id_text', $this->userRepository->language($this->chat_id));
                $title = $this->textRepository->getOrCreate('appeals_history_title_text', $this->userRepository->language($this->chat_id));
                $date = $this->textRepository->getOrCreate('appeals_history_date_text', $this->userRepository->language($this->chat_id));
                $status_text = $this->textRepository->getOrCreate('appeals_history_status_text', $this->userRepository->language($this->chat_id));
                $admin = $this->textRepository->getOrCreate('appeals_history_admin_text', $this->userRepository->language($this->chat_id));
                $message = "$ID: $appeal->id\n$title: $appeal->title\n$date: $appeal->updated_at\n$status_text: $status\n$admin: $appeal->admin_name";
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $message, 'parse_mode' => 'html']);
            }
        } else {
            $text = $this->textRepository->getOrCreate('no_appeals_now', $this->userRepository->language($this->chat_id));
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        }
        $this->showMainPage();
    }

    public function alreadyRegistered(): void
    {
        $text = $this->textRepository->getOrCreate('already_registered_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function technicalWork(): void
    {
        $text = $this->textRepository->getOrCreate('technical_work', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    private function back($step, $function): void
    {
        $this->userRepository->page($this->chat_id, $step);
        $this->$function();
    }

    private function backButton(): bool|string
    {
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $option = [[$this->telegram->buildKeyboardButton($backButton), $this->telegram->buildKeyboardButton($textButtonMain)]];
        return $this->telegram->buildKeyBoard($option, false, true);
    }

    public function handleRegistration(): void
    {
        $user = $this->userRepository->checkOrCreate($this->chat_id);
        if ($user['exists']) {
            $this->alreadyRegistered();
        } else {
            $this->chooseLanguage();
        }
    }

    public function send($chat_id, $message)
    {
        return $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'html']);
    }
}
