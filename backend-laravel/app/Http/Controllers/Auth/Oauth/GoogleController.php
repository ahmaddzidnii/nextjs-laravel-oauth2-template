<?php

namespace App\Http\Controllers\Auth\Oauth;

use App\Services\AuthService;
use App\Traits\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleCallbackRequest;

class GoogleController extends Controller
{
    use ApiResponseHelper;
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function callback(GoogleCallbackRequest $request)
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
            Log::error($e->getMessage(), [
                'context' => [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'request_ip' => $request->ip(),
                    'request_url' => $request->fullUrl(),
                    'request_method' => $request->method(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            $statusCode = $e->getCode() ?: 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}
