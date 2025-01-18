<?php

namespace App\Helpers;

use App\Exceptions\JwtException;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JwtHelpers
{
    protected $configJwt;

    public function __construct()
    {
        $this->configJwt = [
            'issuser' => env('APP_URL'),
            'jwtSecret' => env('JWT_SECRET'),
            'defaultJwtExpiration' => Carbon::now()->addDays(7)->timestamp,
        ];
    }

    public function createToken($user, $expiresIn = null)
    {
        $issued_at = Carbon::now()->timestamp;
        $jti = Str::uuid()->toString();

        $payload = [
            'iss' => $this->configJwt['issuser'],
            'iat' => $issued_at,
            'jti' => $jti,
            'exp' => $expiresIn ?? $this->configJwt['defaultJwtExpiration'],
            'sub' => $user['id'],
            'username' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar' => $user['avatar'],
        ];


        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }

    public function validateToken($token)
    {
        try {
            // Decode token
            $decoded = JWT::decode($token, new Key($this->configJwt['jwtSecret'], 'HS256'));

            // Cek issuer (optional, sesuaikan dengan kebutuhan)
            if (isset($decoded->iss) && $decoded->iss !== $this->configJwt['issuser']) {
                throw new JwtException('Token is not valid', 401);
            }

            // Token valid
            return [
                'valid' => true,
                'decoded' => $decoded,
                'status' => 200
            ];
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new JwtException('Token has expired', 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new JwtException('Token is not valid', 401);
        } catch (\Exception $e) {
            Log::error("Error JwtHelpers validateToken:", ['error' => $e->getMessage()]);
            throw new JwtException('Token is not valid', 401);
        }
    }
}
