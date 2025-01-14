<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Traits\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class TokenController extends Controller
{
    use ApiResponseHelper;
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function refresh(Request $request)
    {
        try {
            $tokens = $this->authService->handleRefreshToken($request);
            $newAccessTokenCookie = cookie(name: 'access_token', value: $tokens['access_token'], secure: env("APP_ENV") != "local", httpOnly: false);
            return $this->successResponse([
                'access_token' => $tokens['access_token'],
            ])->withCookie($newAccessTokenCookie);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $statusCode = $e->getCode();
            return $this->errorResponse($statusCode == 500 ? "Internal Server Error" : $e->getMessage(), $statusCode);
        }
    }


    public function logout(Request $request)
    {
        try {
            $this->authService->handleLogout($request);
            return $this->successResponse([
                'message' => 'Successfully logged out',
            ])->withoutCookie('refresh_token')->withoutCookie('access_token');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $statusCode = $e->getCode();
            return $this->errorResponse($statusCode == 500 ? "Internal Server Error" : $e->getMessage(), $statusCode);
        }
    }
}
