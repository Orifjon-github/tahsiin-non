<?php

namespace App\Http\Middleware;

use App\Services\LogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Logger
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $start_time = microtime(true);
            $logID = uniqid();
            $this->logService->request('elk', $logID, $request->getRequestUri(), $request->getContent());
            $response = $next($request);
            $end_time = microtime(true);
            $elapsed_time = ($end_time - $start_time);
            $this->logService->response('elk', $logID, $response->getStatusCode(), $response->getContent(), time: ceil($elapsed_time));
            return $response;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $next($request);
        }
    }

}
