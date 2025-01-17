<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Helpers\JwtHelpers;
use App\Models\BlacklistedToken;
use App\Exceptions\AuthException;

class TokenRepository
{
    private readonly JwtHelpers $jwtHelpers;

    public function __construct(JwtHelpers $jwtHelpers)
    {
        $this->jwtHelpers = $jwtHelpers;
    }

    public function blacklistToken($token)
    {
        $decodedToken = null;

        try {
            $decodedToken = $this->jwtHelpers->validateToken($token)['decoded'];
        } catch (\Throwable $th) {
            throw new AuthException();
        }

        $expiresAt = Carbon::createFromTimestamp($decodedToken->exp);
        $blacklistToken = new BlacklistedToken();
        $blacklistToken->token = $token;
        $blacklistToken->expires_at = $expiresAt;
        $blacklistToken->save();
    }
}
