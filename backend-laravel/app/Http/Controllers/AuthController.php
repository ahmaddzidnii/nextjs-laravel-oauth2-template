<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\BlacklistedToken;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponseHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');

            if (!$refreshToken) {
                return response()->json(ResponseHelper::error('Token tidak diberikan', [], 401), 401);
            }

            $validatedRefreshToken = $this->authService->validateToken($refreshToken);

            if (!$validatedRefreshToken['valid']) {
                return response()->json(ResponseHelper::error($validatedRefreshToken['message'], [], $validatedRefreshToken['status']), $validatedRefreshToken['status']);
            }



            $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
                $query->where('refresh_token', $refreshToken);
            })->first();

            if (!$user) {
                return response()->json(ResponseHelper::error('Token tidak valid', [], 401), 401);
            }

            $newAccessToken = $this->authService->claimsJWT($user, Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp);

            $newAccessTokenCookie = cookie(name: 'access_token', value: $newAccessToken, secure: env("APP_ENV") != "local", httpOnly: false);

            return response()->json(ResponseHelper::success('Berhasil mendapatkan akses token baru', [
                'access_token' => $newAccessToken,
            ]))->withCookie($newAccessTokenCookie);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $statusCode = $th->getCode();
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500;
            }

            return response()->json(ResponseHelper::error(message: "Internal Server Error", code: $statusCode), $statusCode);
        }
    }


    public function logout(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');
            $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');
            if (!$refreshToken) {
                return response()->json(ResponseHelper::error('Token tidak diberikan', [], 401), 401);
            }

            $validatedRefreshToken = $this->authService->validateToken($refreshToken);
            $validatedAccessToken = $this->authService->validateToken($accessToken);

            if (!$validatedAccessToken['valid']) {
                return response()->json(ResponseHelper::error($validatedAccessToken['message'], [], $validatedAccessToken['status']), $validatedAccessToken['status']);
            }

            if (!$validatedRefreshToken['valid']) {
                return response()->json(ResponseHelper::error($validatedRefreshToken['message'], [], $validatedRefreshToken['status']), $validatedRefreshToken['status']);
            }


            $this->authService->blacklistToken($accessToken);

            $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
                $query->where('refresh_token', $refreshToken);
            })->first();

            if (!$user) {
                return response()->json(ResponseHelper::error('Token tidak valid', [], 401), 401);
            }

            $user->sessions()->where('refresh_token', $refreshToken)->delete();

            return response()->json(ResponseHelper::success('Berhasil logout'))->withoutCookie('refresh_token')->withoutCookie('access_token');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $statusCode = $th->getCode();
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500;
            }

            return response()->json(ResponseHelper::error(message: "Internal Server Error", code: $statusCode), $statusCode);
        }
    }

    public function me(Request $request)
    {
        try {
            $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');

            if (!$accessToken) {
                return response()->json(ResponseHelper::error('Token tidak diberikan', [], 401), 401);
            }

            $validatedToken = $this->authService->validateToken($accessToken);

            if (!$validatedToken['valid']) {
                return response()->json(ResponseHelper::error($validatedToken['message'], [], $validatedToken['status']), $validatedToken['status']);
            }

            $isBlacklisted = BlacklistedToken::where('token', $accessToken)->exists();

            if ($isBlacklisted) {
                return response()->json(ResponseHelper::error('Token tidak valid', [], 401), 401);
            }

            $user = [
                'user_id' => $validatedToken['decoded']->sub,
                'username' => $validatedToken['decoded']->username,
                'email' => $validatedToken['decoded']->email,
                'avatar' => $validatedToken['decoded']->avatar,
                'role' => $validatedToken['decoded']->role,
            ];

            return response()->json(ResponseHelper::success('Berhasil mendapatkan data user', [
                'user' => $user,
            ]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $statusCode = $th->getCode();
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500;
            }

            return response()->json(ResponseHelper::error(message: "Internal Server Error", code: $statusCode), $statusCode);
        }
    }
}
