<?php

namespace App\Http\Controllers;

use App\Enums\ResponseCodeEnum;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Jiannei\Response\Laravel\Support\Facades\Response;

class AuthController extends Controller
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    /**
     * 用户注册
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => '请输入用户名',
            'email.required' => '请输入邮箱',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '该邮箱已被注册',
            'password.required' => '请输入密码',
            'password.min' => '密码至少6位',
            'password.confirmed' => '两次密码不一致',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $this->tokenService->createToken($user);

        return Response::success([
            'user' => $user,
            'token' => $token,
        ], '', ResponseCodeEnum::AUTH_REGISTER_SUCCESS);
    }

    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => '请输入邮箱',
            'email.email' => '邮箱格式不正确',
            'password.required' => '请输入密码',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return Response::fail('', ResponseCodeEnum::AUTH_INVALID_CREDENTIALS);
        }

        $token = $this->tokenService->createToken($user);

        return Response::success([
            'user' => $user,
            'token' => $token,
        ], '', ResponseCodeEnum::AUTH_LOGIN_SUCCESS);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            $this->tokenService->deleteToken($token);
        }

        return Response::success(null, '', ResponseCodeEnum::AUTH_LOGOUT_SUCCESS);
    }

    /**
     * 获取当前用户信息
     */
    public function me(Request $request)
    {
        return Response::success($request->user());
    }
}
