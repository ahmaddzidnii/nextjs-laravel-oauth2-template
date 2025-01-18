<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AuthException;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Traits\ApiResponseHelper;
use App\Http\Controllers\Controller;

class TokenController extends Controller
{
    use ApiResponseHelper;

    public function __construct(protected readonly AuthService $authService) {}

    public function refresh(Request $request)
    {
        // Get refresh token from cookie, bearer token, or query string
        $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');

        if (!$refreshToken) {
            throw new AuthException("Token is not given", 401);
        }

        $data = $this->authService->refreshAccessToken($refreshToken);
        $newAccessTokenCookie = cookie(
            name: 'access_token',
            value: $data['access_token'],
            secure: env("APP_ENV") != "local",
            httpOnly: false
        );
        return $this->successResponse($data)->withCookie($newAccessTokenCookie);
    }

    public function logout(Request $request)
    {
        $this->authService->handleLogout($request);
        return $this->successResponse([
            'message' => 'Successfully logged out',
        ])->withoutCookie('refresh_token')->withoutCookie('access_token');
    }
}
