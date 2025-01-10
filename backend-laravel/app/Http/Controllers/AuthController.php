<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleApiException;
use App\Helpers\ResponseHelper;
use App\Models\BlacklistedToken;
use App\Models\Session;
use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
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
                return response()->json(ResponseHelper::error('Kode tidak diberikan', [], 400), 400);
            }


            $tokens = $this->authService->exchangeCode($code);

            $userInfo = $this->authService->getUserInfo($tokens['access_token']);

            $user = User::firstOrCreate(
                ['provider_id' => $userInfo['id']],
                [
                    'provider_id' => $userInfo['id'],
                    'username' => $userInfo['name'],
                    'email' => $userInfo['email'],
                    'avatar' => $userInfo['picture'],
                    'role' => 'user',
                ]
            );

            $accessToken = $this->authService->claimsJWT($user, Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp);

            $refreshToken = $this->authService->claimsJWT($user, Carbon::now()->addMinutes((int) env('JWT_REFRESH_TOKEN_EXPIRATION'))->timestamp);

            // Cek apakah session sudah ada
            $existingSession = Session::where('user_id', $user->user_id)
                ->where('user_agent', $user_agent)
                ->first();

            if ($existingSession) {
                // Update session yang sudah ada
                $existingSession->update([
                    'refresh_token' => $refreshToken,
                    'last_login' => now()->getPreciseTimestamp(3),
                ]);
            } else {
                // Buat session baru jika belum ada
                Session::create([
                    'user_id' => $user->user_id,
                    'user_agent' => $user_agent,
                    'refresh_token' => $refreshToken,
                    'last_login' => now()->getPreciseTimestamp(3),
                ]);
            }

            $accessTokenCookie = cookie(name: 'access_token', value: $accessToken, secure: env("APP_ENV") != "local", httpOnly: false);

            $cookieRefreshToken = cookie(
                name: 'refresh_token',
                value: $refreshToken,
                secure: env("APP_ENV") != "local",
                httpOnly: true
            );

            return response()->json(ResponseHelper::success('Berhasil login dengan Google', [
                'access_token' => $accessToken,
            ]))->withCookie($cookieRefreshToken)->withCookie($accessTokenCookie);
        } catch (GoogleApiException $e) {
            return response()->json(ResponseHelper::error($e->getMessage(), [], $e->getStatusCode()), $e->getStatusCode());
        } catch (\Throwable $th) {
            // Log error detail untuk debugging
            Log::error($th->getMessage());

            // Berikan respon yang informatif untuk klien
            $statusCode = $th->getCode();
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500; // Default ke Internal Server Error
            }

            return response()->json(ResponseHelper::error(message: "Internal Server Error", code: $statusCode), $statusCode);
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
