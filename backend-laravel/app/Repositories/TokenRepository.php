<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Helpers\JwtHelpers;
use App\Models\BlacklistedToken;
use App\Exceptions\AuthException;

class TokenRepository
{
    public function __construct(protected readonly JwtHelpers $jwtHelpers) {}

    public function blacklistToken($token)
    {
        try {
            $decodedToken = $this->jwtHelpers->validateToken($token)['decoded'];
            $jti = $decodedToken->jti;
        } catch (\Throwable $th) {
            throw new AuthException();
        }

        $expiresAt = Carbon::createFromTimestamp($decodedToken->exp, config('app.timezone'));
        BlacklistedToken::create([
            'jti' => $jti,
            'expires_at' => $expiresAt,
        ]);
    }

    public function isTokenBlacklisted($token)
    {
        try {
            $decodedToken = $this->jwtHelpers->validateToken($token)['decoded'];
            $jti = $decodedToken->jti;
        } catch (\Throwable $th) {
            throw new AuthException();
        }

        return BlacklistedToken::where('jti', $jti)->exists();
    }
}
