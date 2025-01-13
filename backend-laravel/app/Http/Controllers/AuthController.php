<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Traits\ApiResponseHelper;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponseHelper;
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function googleCallback(Request $request)
    {
        try {
            $credentials = $this->authService->handleGoogleLogin($request);
            $accessToken = $credentials['access_token'];
            $refreshToken = $credentials['refresh_token'];

            $accessTokenCookie = cookie(name: 'access_token', value: $accessToken, secure: env("APP_ENV") != "local", httpOnly: false);
            $cookieRefreshToken = cookie(name: 'refresh_token', value: $refreshToken, secure: env("APP_ENV") != "local", httpOnly: true);

            return $this->successResponse([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ])->withCookie($cookieRefreshToken)->withCookie($accessTokenCookie);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $statusCode = $e->getCode();
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
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
                'message' => 'Berhasil logout',
            ])->withoutCookie('refresh_token')->withoutCookie('access_token');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $statusCode = $e->getCode();
            return $this->errorResponse($statusCode == 500 ? "Internal Server Error" : $e->getMessage(), $statusCode);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $this->authService->handleGetMe($request);
            return $this->successResponse($user);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $statusCode = $e->getCode();
            return $this->errorResponse($statusCode == 500 ? "Internal Server Error" : $e->getMessage(), $statusCode);
        }
    }
}
