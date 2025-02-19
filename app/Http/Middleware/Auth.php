<?php

namespace App\Http\Middleware;

use App\Helpers\Response;
use Closure;
use Illuminate\Http\Request;

class Auth
{
    use Response;
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        $telegram_ips = [
            '149.154.160.0/20',
            '91.108.4.0/22'
        ];
        $ip = $request->ip();
        foreach ($telegram_ips as $allowed_ip) {
            if ($this->ip_in_range($ip, $allowed_ip)) {
                return $next($request);
            }
        }

        $token = $request->header('Authorization');
        if (!$token) {
            $this->error(__('Unauthorized'), 401);
        }

        if (!in_array($ip, config('auth.ips'))) {
            $this->error(__("Unauthorized: $ip"), 401);
        }
        $client = config('auth.clients')[$token] ?? null;
        if (!$client) {
            $this->error(__('Unauthorized'), 401);
        }
        $request->headers->set('Client', $client);
        $request->headers->set('RequestCode', uniqid());

        return $next($request);
    }

    private function ip_in_range($ip, $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip_dec = ip2long($ip);
        $subnet_dec = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        return ($ip_dec & $mask) === ($subnet_dec & $mask);
    }
}
