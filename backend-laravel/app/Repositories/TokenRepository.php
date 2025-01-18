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
        $decodedToken = null;

        try {
            $decodedToken = $this->jwtHelpers->validateToken($token)['decoded'];
        } catch (\Throwable $th) {
            throw new AuthException();
        }

        $expiresAt = Carbon::createFromTimestamp($decodedToken->exp, config('app.timezone'));
        BlacklistedToken::create([
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }
}
