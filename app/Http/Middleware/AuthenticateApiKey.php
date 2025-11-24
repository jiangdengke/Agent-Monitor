<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCodeEnum;
use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;        // 返回类型
use Jiannei\Response\Laravel\Response as ApiResponse; // 调用方法
/**
 * 验证Agent请求的API KEY是否有效
 */
class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        // 获取API key
        $apiKey = $request->header('X-API-Key') ?? $request->bearerToken();
        if (!$apiKey){
            return ApiResponse::fail('',ResponseCodeEnum::API_KEY_REQUIRED);
        }
        // 查找API Key
        $key = ApiKey::where('key', $apiKey)->first();
        if (!$key){
            return ApiResponse::fail('',ResponseCodeEnum::API_KEY_INVALID);
        }
        // 检查是否过期
        if (!$key->isValid()){
            return ApiResponse::fail('',ResponseCodeEnum::API_KEY_EXPIRED);
        }

        // 将api key信息附加到请求，供后续控制器使用
        $request->merge(['api_key' => $key]);
        return $next($request);
    }
}
