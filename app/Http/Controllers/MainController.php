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
        return $this->success(['ip' => $request->ip()]);
    }
}
