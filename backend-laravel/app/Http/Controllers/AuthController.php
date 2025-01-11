<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\BlacklistedToken;
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
            $user_agent = $request->userAgent();
            $code = $request->query('code');

            if (!$code) {
                return $this->errorResponse("Kode tidak diberikan", 400);
            }

            $credentials = $this->authService->handleGoogleLogin($code, $user_agent);
            $accessToken = $credentials['access_token'];
            $refreshToken = $credentials['refresh_token'];

            $accessTokenCookie = cookie(name: 'access_token', value: $accessToken, secure: env("APP_ENV") != "local", httpOnly: false);
            $cookieRefreshToken = cookie(
                name: 'refresh_token',
                value: $refreshToken,
                secure: env("APP_ENV") != "local",
                httpOnly: true
            );

            return $this->successResponse([
                'access_token' => $accessToken,
            ])->withCookie($cookieRefreshToken)->withCookie($accessTokenCookie);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            $statusCode = $th->getCode();
            return $this->errorResponse($th->getMessage(), $statusCode);
        }
    }


    public function refresh(Request $request)
    {
        try {
            // Get refresh token from cookie, bearer token, or query string
            $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');

            if (!$refreshToken) {
                return $this->errorResponse("Token tidak diberikan", 401);
            }

            // Validate refresh token
            $validatedRefreshToken = $this->authService->validateToken($refreshToken);

            if (!$validatedRefreshToken['valid']) {
                return $this->errorResponse($validatedRefreshToken['message'], $validatedRefreshToken['status']);
            }

            // Check user refresh token in database
            $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
                $query->where('refresh_token', $refreshToken);
            })->first();

            if (!$user) {
                return $this->errorResponse('Token tidak valid', 401);
            }

            $newAccessToken = $this->authService->claimsJWT($user, Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp);
            $newAccessTokenCookie = cookie(name: 'access_token', value: $newAccessToken, secure: env("APP_ENV") != "local", httpOnly: false);

            return $this->successResponse([
                'access_token' => $newAccessToken,
            ])->withCookie($newAccessTokenCookie);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $statusCode = $th->getCode();
            return $this->errorResponse("Internal Server Error", $statusCode);
        }
    }


    public function logout(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');
            $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');
            if (!$refreshToken) {
                return $this->errorResponse("Token tidak diberikan", 401);
            }

            $validatedRefreshToken = $this->authService->validateToken($refreshToken);
            $validatedAccessToken = $this->authService->validateToken($accessToken);

            if (!$validatedAccessToken['valid']) {
                return $this->errorResponse($validatedAccessToken['message'], $validatedAccessToken['status']);
            }

            if (!$validatedRefreshToken['valid']) {
                return $this->errorResponse($validatedRefreshToken['message'], $validatedRefreshToken['status']);
            }

            $this->authService->blacklistToken($accessToken);

            $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
                $query->where('refresh_token', $refreshToken);
            })->first();

            if (!$user) {
                return $this->errorResponse('Token tidak valid', 401);
            }

            $user->sessions()->where('refresh_token', $refreshToken)->delete();
            return $this->successResponse([
                'message' => 'Berhasil logout',
            ])->withoutCookie('refresh_token')->withoutCookie('access_token');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $statusCode = $th->getCode();

            return $this->errorResponse("Internal Server Error", $statusCode);
        }
    }

    public function me(Request $request)
    {
        try {
            $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');

            if (!$accessToken) {
                return $this->errorResponse("Token tidak diberikan", 401);
            }

            $validatedToken = $this->authService->validateToken($accessToken);

            if (!$validatedToken['valid']) {
                return $this->errorResponse($validatedToken['message'], $validatedToken['status']);
            }

            $isBlacklisted = BlacklistedToken::where('token', $accessToken)->exists();

            if ($isBlacklisted) {
                return $this->errorResponse("Token sudah tidak valid", 401);
            }

            $user = [
                'user_id' => $validatedToken['decoded']->sub,
                'username' => $validatedToken['decoded']->username,
                'email' => $validatedToken['decoded']->email,
                'avatar' => $validatedToken['decoded']->avatar,
                'role' => $validatedToken['decoded']->role,
            ];

            return $this->successResponse($user);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $statusCode = $th->getCode();

            return $this->errorResponse("Internal Server Error", $statusCode);
        }
    }
}
