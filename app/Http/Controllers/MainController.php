<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Http\Resources\ChatDetailResource;
use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ExportService;
use App\Services\LogService;
use App\Services\Telegram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MainController extends Controller
{
    use Response;

    public function ping(Request $request): JsonResponse
    {
        return $this->success(['ip' => 'test']);
    }

    public function openChats(Request $request): JsonResponse
    {
        $openChats = Chat::where('status', 'ready')->get();
        return $this->success(ChatResource::collection($openChats));
    }

    public function chatDetail($id, Request $request): JsonResponse
    {
        $adminID = $request->input('admin_id');
        if (!$adminID) return $this->error('admin_id is required');

        $chat = Chat::find($id);
        if (!$chat) return $this->error('Chat not found');

        if ($chat->status == 'active') {
            if ($chat->admin_id != $adminID) return $this->error('You cannot see another employee\'s active chat');
        };
        return $this->success(ChatDetailResource::make($chat));
    }

    public function adminChats(Request $request): JsonResponse
    {
        $adminID = $request->input('admin_id');
        if (!$adminID) return $this->error('admin_id is required');

        $chatStatus = $request->input('status') ?? 'active';

        $adminChats = Chat::where('admin_id', $adminID)->where('status', $chatStatus)->get();
        return $this->success(ChatResource::collection($adminChats));
    }

    public function activate(Request $request): JsonResponse
    {
        $admin_id = $request->input('admin_id');
        $chat_id = $request->input('chat_id');
        $admin_name = $request->input('admin_name');
        if (!$admin_id && !$chat_id && !$admin_name) return $this->error('Not enough params');

        $chat = Chat::find($chat_id);
        if ($chat->status != 'ready') return $this->error('Chat is not ready to activate. Chat may be activated another admin or closed');

        $chat->status = 'active';
        $chat->admin_id = $admin_id;
        $chat->admin_name = $admin_name;
        $chat->save();

        $telegram = new Telegram(new LogService());
        $message = [
            'uz' => "Sizning so'rovingiz qayta ishlash uchun qabul qilindi. Tez orada operator sizga javob beradi. Operator: $admin_name",
            'ru' => "Ваш запрос принят в обработку. Оператор ответит вам в ближайшее время. Оператор: $admin_name",
            'en' => "Your request has been accepted for processing. The operator will answer you soon. Operator: $admin_name"
        ];
        $telegram->sendMessage(['chat_id' => $chat->user->chat_id, 'text' => $message[$chat->user->language]]);
        return $this->success(ChatDetailResource::make($chat));
    }

    public function close(Request $request): JsonResponse
    {
        $admin_id = $request->input('admin_id');
        $chat_id = $request->input('chat_id');
        $text = $request->input('message');

        if (!$admin_id && !$chat_id) return $this->error('Not enough params');

        $chat = Chat::find($chat_id);
        if ($chat->status != 'active') return $this->error('Chat is not ready to close. Chat status is not active');

        $chat->status = 'close';
        $chat->save();

        $telegram = new Telegram(new LogService());
        if (!$text) {
            $message = [
                'uz' => "Sizdan javob ololmadik, chatni yakunlashga majburmiz. Savollar bo‘lsa biz sizga yordam berishga tayyormiz",
                'ru' => "Мы не смогли получить от вас ответа, вынуждены завершить чат. Если у вас есть вопросы, мы готовы вам помочь",
                'en' => "We couldn't get a response from you, we have to end the chat. If you have any questions, we are ready to help you"
            ];
        } else {
            $message = [
                'uz' => $text,
                'ru' => $text,
                'en' => $text
            ];
        }
        $textButton = [
            'uz' => 'Asosiy Sahifaga qaytish',
            'ru' => 'Вернуться на главную страницу',
            'en' => 'Return to Main Page',
        ];
        $option = [[$telegram->buildKeyboardButton($textButton[$chat->user->language])]];
        $keyboard = $telegram->buildKeyBoard($option, false, true);
        $telegram->sendMessage(['chat_id' => $chat->user->chat_id, 'reply_markup' => $keyboard, 'text' => $message[$chat->user->language]]);
        $userRepo = new UserRepository(new User());
        $userRepo->page($chat->user->chat_id, 'main');
        return $this->success(ChatDetailResource::make($chat));
    }

    public function sendMessage(Request $request)
    {
        $admin_id = $request->input('admin_id');
        $chat_id = $request->input('chat_id');
        $text = $request->input('message');

        if (!$admin_id && !$chat_id && !$text) return $this->error('Not enough params');

        $chat = Chat::find($chat_id);

        if ($chat->status != 'active') return $this->error('Chat is not active. You cannot send message inactive chat');

        if ($chat->admin_id != $admin_id) return $this->error('You cannot send message to another admin chat');

        $chat->messages()->create(['message' => $text, 'owner' => 'admin', 'admin_id' => $admin_id]);
        $telegram = new Telegram(new LogService());
        $telegram->sendMessage(['chat_id' => $chat->user->chat_id, 'text' => $text]);
        return $this->success(ChatDetailResource::make($chat));
    }

    public function all(Request $request): JsonResponse
    {
        $query = Chat::query();
        if ($request->has('appeal_type_id')) $query->where('appeal_type_id', $request->input('appeal_type_id'));
        if ($request->has('start_date')) $query->where('created_at', '>=', $request->input('start_date'));
        if ($request->has('end_date')) $query->where('created_at', '<=', $request->input('end_date'));
        $query->where('status', 'close')->orderBy('created_at', 'desc');
        return $this->success(ChatResource::collection($query->get()));
    }

    public function metric(Request $request): JsonResponse
    {
        $query = Chat::query();

        if ($request->has('appeal_type_id')) {
            $query->where('appeal_type_id', $request->input('appeal_type_id'));
        }
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        $result = $query->selectRaw('
        DATE(created_at) as date,
        appeal_type_id,
        status,
        COUNT(*) as count
    ')
            ->groupByRaw('DATE(created_at), appeal_type_id, status')
            ->get();

        $labels = ['Date', 'Appeal Type', 'Status', 'Count'];
        $items = [];

        foreach ($result as $item) {
            $items[] = [
                $item->date,
                $item->appeal_type_id ?? 'N/A',
                $item->status,
                $item->count,
            ];
        }

        $excel_service = new ExportService();
        $data = [
            'excel' => [
                'columns' => $labels,
                'data' => $items,
                'save' => 'minio',
                'bucket_name' => 'ibank-bot-report',
                'filename' => 'chat_stats_' . now()->format('Y_m_d_H_i_s'),
            ]
        ];

        $result = $excel_service->send($data);

        if ($result['status']) {
            return $this->success(['url' => $result['url']]);
        }

        return $this->error($result['message']);
    }
}
