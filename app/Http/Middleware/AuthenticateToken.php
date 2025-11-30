<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCodeEnum;
use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthenticateToken
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    public function handle(Request $request, Closure $next): HttpResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return Response::fail('', ResponseCodeEnum::AUTH_TOKEN_INVALID);
        }

        $user = $this->tokenService->validateToken($token);

        if (!$user) {
            return Response::fail('', ResponseCodeEnum::AUTH_TOKEN_EXPIRED);
        }

        // 设置当前用户
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
