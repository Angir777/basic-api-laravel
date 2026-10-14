<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\LoginService;
use App\Services\Auth\LogoutService;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    protected $loginService;

    protected $logoutService;

    public function __construct(LoginService $loginService, LogoutService $logoutService)
    {
        $this->loginService = $loginService;
        $this->logoutService = $logoutService;
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        return $this->loginService->login(credentials: $credentials);
    }

    public function logout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'revokeAll' => ['nullable', 'boolean'],
        ]);

        $data = $this->logoutService->logout(data: $validated);

        return ResponseUtils::response(data: $data);
    }

    public function getAdminsEmails(Request $request): JsonResponse
    {
        $adminRole = User::whereHas(
            'roles',
            function ($q) {
                $q->whereIn('name', [config('access.roles.admin')]);
            }
        )->pluck('email')->toArray();

        return ResponseUtils::response(data: $adminRole);
    }
}
