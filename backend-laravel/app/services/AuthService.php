<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Session;
use App\Helpers\JwtHelpers;
use Illuminate\Http\Request;
use App\Models\BlacklistedToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Exceptions\GoogleApiException;
use App\Http\Requests\Auth\GoogleCallbackRequest;

class AuthService
{
    protected $jwtHelpers;

    public function __construct(JwtHelpers $jwtHelpers)
    {
        $this->jwtHelpers = $jwtHelpers;;
    }

    public function handleGoogleLogin(GoogleCallbackRequest $request)
    {
        try {
            $code = $request->validated()['code'];
            $user_agent = $request->userAgent();

            $tokens = $this->exchangeCode($code);
            $userInfo = $this->getUserInfo($tokens['access_token']);
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

            $accessToken = $this->jwtHelpers->createToken($user, Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp);
            $refreshToken = $this->jwtHelpers->createToken($user, Carbon::now()->addMinutes((int) env('JWT_REFRESH_TOKEN_EXPIRATION'))->timestamp);

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

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ];
        } catch (GoogleApiException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            throw new Exception($e->getMessage(), 500);
        } catch (Exception $e) {
            throw $e;
        }
    }


    private function handleGoogleError($response)
    {

        $status = $response->status();

        // Pemetaan error custom
        $message = match ($status) {
            400 => 'Code tidak valid. Silakan coba lagi.',
            401 => 'Unauthorized. Silakan login terlebih dahulu.',
            403 => 'Forbidden. Anda tidak memiliki akses ke halaman ini.',
            500 => 'Server google sedang bermasalah. Silakan coba lagi nanti.',
            default => 'Terjadi kesalahan. Silakan coba lagi.',
        };

        // Lempar exception
        throw new GoogleApiException($message, $status);
    }

    public function exchangeCode($code)
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
        ]);

        Log::info("Token dari google :", $response->json());

        if ($response->failed()) {
            $this->handleGoogleError($response);
        }

        return $response->json();
    }


    public function getUserInfo($access_token)
    {

        $response_userInfo = Http::get('https://www.googleapis.com/oauth2/v1/userinfo', [
            'access_token' => $access_token,
        ]);

        Log::info("User Info dari google :", $response_userInfo->json());

        if ($response_userInfo->failed()) {
            $this->handleGoogleError($response_userInfo);
        }

        return $response_userInfo->json();
    }

    public  function handleRefreshToken(Request $request)
    {
        try {
            // Get refresh token from cookie, bearer token, or query string
            $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');

            if (!$refreshToken) {
                throw new Exception("Token is not given", 401);
            }

            $this->jwtHelpers->validateToken($refreshToken);

            // Check user refresh token in database
            $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
                $query->where('refresh_token', $refreshToken);
            })->first();

            if (!$user) {
                throw new Exception("User not found", 404);
            }

            $accessTokenExpiresIn = Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp;

            $newAccessToken = $this->jwtHelpers->createToken($user, $accessTokenExpiresIn);

            return [
                'access_token' => $newAccessToken,
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            throw new Exception($e->getMessage(), 500);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function handleLogout(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');
            $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');
            if (!$refreshToken) {
                throw new Exception("Token is not given", 401);
            }

            $this->jwtHelpers->validateToken($refreshToken);
            $this->jwtHelpers->validateToken($accessToken);


            $this->blacklistToken($accessToken);

            $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
                $query->where('refresh_token', $refreshToken);
            })->first();

            if (!$user) {
                throw new Exception("Token is not valid", 401);
            }

            $user->sessions()->where('refresh_token', $refreshToken)->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            throw new Exception($e->getMessage(), 500);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function handleGetMe(Request $request)
    {
        try {
            $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');

            if (!$accessToken) {
                throw new Exception("Token is not given", 401);
            }

            $this->jwtHelpers->validateToken($accessToken);

            $isBlacklisted = BlacklistedToken::where('token', $accessToken)->exists();

            if ($isBlacklisted) {
                throw new Exception("Token is not valid", 401);
            }

            $user = $request->attributes->get('user');

            $responseUser = [
                'user_id' => $user->sub,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
            ];

            return $responseUser;
        } catch (\Exception $th) {
            throw $th;
        }
    }

    public function blacklistToken($token)
    {
        try {

            $decodedToken = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $expiresAt = Carbon::createFromTimestamp($decodedToken->exp);

            $blacklistToken = new BlacklistedToken();
            $blacklistToken->token = $token;
            $blacklistToken->expires_at = $expiresAt;
            $blacklistToken->save();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
