<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Session;
use App\Enums\ProviderEnum;
use App\Helpers\JwtHelpers;
use Illuminate\Http\Request;
use App\Models\BlacklistedToken;
use App\Exceptions\AuthException;
use App\Helpers\GoogleOAuthHelper;
use App\Repositories\AccountRepository;
use App\Repositories\SessionRepository;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(
        protected JwtHelpers $jwtHelpers,
        protected TokenRepository $tokenRepository,
        protected UserRepository $userRepository,
        protected SessionRepository $sessionRepository,
        protected AccountRepository $accountRepository
    ) {}

    public function handleGoogleLogin(string $code, string $userAgent)
    {
        DB::beginTransaction();
        try {
            $tokens = GoogleOAuthHelper::exchangeCode($code);
            $userInfo = GoogleOAuthHelper::getUserInfo($tokens['access_token']);

            // Check if account already exists
            $existingAccount = $this->accountRepository->findByProviderId($userInfo['id'], 'google');

            if ($existingAccount) {
                $user = $existingAccount->user;
            } else {
                // Check if user email already exists
                $user = $this->userRepository->findByEmail($userInfo['email']);

                if (!$user) {
                    // Create new user if not exists
                    $user = $this->userRepository->createUser([
                        'name' => $userInfo['name'],
                        'email' => $userInfo['email'],
                        'avatar' => $userInfo['picture'],
                        'role' => 'user',
                    ]);

                    // create new account linked to user
                    $this->accountRepository->createAccount($user->id, ProviderEnum::GOOGLE, $userInfo['id']);
                }
            }

            // Generate tokens
            $accessToken = $this->jwtHelpers->createToken(
                $user,
                Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp
            );

            $refreshToken = $this->jwtHelpers->createToken(
                $user,
                Carbon::now()->addMinutes((int) env('JWT_REFRESH_TOKEN_EXPIRATION'))->timestamp
            );

            // Check if there's an existing active session for this user agent
            $existingSession = $this->sessionRepository->getSessionByUserAndAgent($user->id, $userAgent);

            if ($existingSession) {
                // Update existing session
                $existingSession->update([
                    'refresh_token' => $refreshToken,
                    'last_login' => now()->timestamp,
                    'ip' => request()->ip()
                ]);
            } else {
                // Create new session
                $this->sessionRepository->createNewSession(
                    $user->id,
                    $userAgent,
                    $refreshToken
                );
            }

            // Get all active sessions for response
            $activeSessions = $this->sessionRepository->getActiveSessionsByUser($user->id);

            DB::commit();

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'active_sessions' => $activeSessions->map(function ($session) {
                    return [
                        'user_agent' => $session->user_agent,
                        'last_login' => Carbon::createFromTimestampMs($session->last_login)->toDateTimeString(),
                        'ip' => $session->ip
                    ];
                })
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function revokeSession(int $userId, string $refreshToken)
    {
        return DB::transaction(function () use ($userId, $refreshToken) {
            return Session::where('user_id', $userId)
                ->where('refresh_token', $refreshToken)
                ->update([
                    'is_active' => false,
                    'updated_at' => now()
                ]);
        });
    }

    public function handleLogout(string $accessToken, string $refreshToken)
    {
        DB::beginTransaction();
        try {
            $this->tokenRepository->blacklistToken($accessToken);
            $this->tokenRepository->blacklistToken($refreshToken);

            $this->sessionRepository->deleteSessionByRefreshToken($refreshToken);
            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function revokeAllSessions(int $userId, ?string $exceptRefreshToken = null)
    {
        return DB::transaction(function () use ($userId, $exceptRefreshToken) {
            $query = Session::where('user_id', $userId)
                ->where('is_active', true);

            if ($exceptRefreshToken) {
                $query->where('refresh_token', '!=', $exceptRefreshToken);
            }

            return $query->update([
                'is_active' => false,
                'updated_at' => now()
            ]);
        });
    }

    public  function refreshAccessToken(string $refreshToken)
    {
        try {
            $this->jwtHelpers->validateToken($refreshToken);
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage(), 401);
        }

        DB::beginTransaction();
        try {
            // Check user refresh token in database
            $session = $this->sessionRepository->findActiveSessionByRefreshToken($refreshToken);

            if (!$session) {
                throw new AuthException("Invalid refresh token or session expired", 401);
            }

            $user = $session->user;

            if (!$user) {
                throw new AuthException("User not found", 404);
            }

            // Generate new access token
            $accessTokenExpiresIn = Carbon::now()
                ->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))
                ->timestamp;

            $newAccessToken = $this->jwtHelpers->createToken($user, $accessTokenExpiresIn);

            // Update session last activity
            $session->update([
                'last_login' => now()->timestamp,
                'updated_at' => now()
            ]);

            DB::commit();

            $accessTokenExpiresIn = Carbon::now()->addMinutes((int) env('JWT_ACCESS_TOKEN_EXPIRATION'))->timestamp;

            return [
                'access_token' => $newAccessToken,
                'session_info' => [
                    'last_login' => Carbon::createFromTimestamp($session->last_login, config('app.timezone'))->toDateTimeString(),
                    'user_agent' => $session->user_agent,
                    'ip' => $session->ip
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
