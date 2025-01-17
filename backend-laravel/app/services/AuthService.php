<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Session;
use App\Helpers\JwtHelpers;
use Illuminate\Http\Request;
use App\Models\BlacklistedToken;
use App\Exceptions\AuthException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\GoogleCallbackRequest;
use App\Repositories\TokenRepository;

class AuthService
{
    protected $jwtHelpers;
    protected $tokenRepository;

    public function __construct(JwtHelpers $jwtHelpers, TokenRepository $tokenRepository)
    {
        $this->jwtHelpers = $jwtHelpers;
        $this->tokenRepository = $tokenRepository;
    }

    public function handleGoogleLogin(GoogleCallbackRequest $request)
    {

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
    }

    public function exchangeCode($code)
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
                'grant_type' => 'authorization_code',
            ])->throw();

            return $response->json();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $statusCode = $e->response->status();

            if ($statusCode >= 400 && $statusCode < 500) {
                // Error karena permintaan tidak valid
                Log::warning('Google OAuth Error: ' . $e->getMessage(), [
                    'context' => [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'status_code' => $statusCode,
                    ],
                ]);

                throw ValidationException::withMessages([
                    'code' => ['Athorization code is not valid. Try again.'],
                ]);
            } elseif ($statusCode >= 500) {
                // Error karena server Google
                Log::error('Google OAuth Server Error: ' . $e->getMessage(), [
                    'context' => [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'status_code' => $statusCode,
                    ],
                ]);

                throw new \RuntimeException('Server Google is not available. Please try again later.');
            }

            // Jika jenis error tidak terdeteksi
            Log::critical('Unexpected Google OAuth Error: ' . $e->getMessage(), [
                'context' => [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'status_code' => $statusCode,
                ],
            ]);

            throw new \RuntimeException('Unexpected error. Please try again later.');
        }
    }

    public function getUserInfo($access_token)
    {
        try {
            $response_userInfo = Http::get('https://www.googleapis.com/oauth2/v1/userinfo', [
                'access_token' => $access_token,
            ])->throw();

            return $response_userInfo->json();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $statusCode = $e->response->status();

            if ($statusCode >= 400 && $statusCode < 500) {
                // Error karena permintaan tidak valid
                Log::warning('Google User Info Error: ' . $e->getMessage(), [
                    'context' => [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'status_code' => $statusCode,
                    ],
                ]);
            } elseif ($statusCode >= 500) {
                // Error karena server Google
                Log::error('Google OAuth Server Error: ' . $e->getMessage(), [
                    'context' => [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'status_code' => $statusCode,
                    ],
                ]);

                throw new \RuntimeException('Server Google is not available. Please try again later.');
            }

            // Jika jenis error tidak terdeteksi
            Log::critical('Unexpected Google OAuth Error: ' . $e->getMessage(), [
                'context' => [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'status_code' => $statusCode,
                ],
            ]);

            throw new \RuntimeException('Unexpected error. Please try again later.');
        }
    }

    public  function handleRefreshToken(Request $request)
    {
        // Get refresh token from cookie, bearer token, or query string
        $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');

        if (!$refreshToken) {
            throw new AuthException("Token is not given", 401);
        }

        try {
            $this->jwtHelpers->validateToken($refreshToken);
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage(), 401);
        }

        // Check user refresh token in database
        $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
            $query->where('refresh_token', $refreshToken);
        })->first();

        if (!$user) {
            throw new AuthException("Token is not valid", 401);
        }

        $accessTokenExpiresIn = Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp;

        $newAccessToken = $this->jwtHelpers->createToken($user, $accessTokenExpiresIn);

        return [
            'access_token' => $newAccessToken,
        ];
    }

    public function handleLogout(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');
        $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');
        if (!$refreshToken) {
            throw new Exception("Token is not given", 401);
        }
        try {
            $this->jwtHelpers->validateToken($refreshToken);
            $this->jwtHelpers->validateToken($accessToken);
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage());
        }

        $this->tokenRepository->blacklistToken($accessToken);

        $user = User::whereHas('sessions', function ($query) use ($refreshToken) {
            $query->where('refresh_token', $refreshToken);
        })->first();

        if (!$user) {
            throw new AuthException("Token is not valid");
        }

        $user->sessions()->where('refresh_token', $refreshToken)->delete();
    }

    public function handleGetMe(Request $request)
    {
        $accessToken = $request->bearerToken() ?? $request->query('access_token') ?? $request->cookie('access_token');

        if (!$accessToken) {
            throw new AuthException("Token is not given");
        }

        $isBlacklisted = BlacklistedToken::where('token', $accessToken)->exists();

        if ($isBlacklisted) {
            throw new AuthException("Token is not valid");
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
    }
}
