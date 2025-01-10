<?php

namespace App\Services;

use App\Exceptions\GoogleApiException;
use App\Models\BlacklistedToken;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

class AuthService
{


    private function handleGoogleError($response)
    {

        $status = $response->status();
        $error_details = $response->json();

        // Pemetaan error custom
        $message = match ($status) {
            400 => $errorDetails['error_description'] ?? 'Permintaan tidak valid. Silakan coba lagi.',
            401 => 'Unauthorized. Silakan login terlebih dahulu.',
            403 => 'Forbidden. Anda tidak memiliki akses ke halaman ini.',
            500 => 'Server google sedang bermasalah. Silakan coba lagi nanti.',
            default => $error_details['error_description'] ?? 'Terjadi kesalahan. Silakan coba lagi.',
        };

        // Lempar exception
        // throw new GoogleApiException($message, $status);
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

    public function claimsJWT($user, $expiresIn = null)
    {
        $issued_at = Carbon::now()->timestamp;
        $payload = [
            'iss' => "backend-laravel",
            'iat' => $issued_at,
            'exp' => $expiresIn ?? Carbon::now()->addDays(7)->timestamp,
            'sub' => $user['user_id'],
            'username' => $user['username'],
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
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            // Cek expired time
            if (isset($decoded->exp) && now()->timestamp >= $decoded->exp) {
                return [
                    'valid' => false,
                    'message' => 'Token sudah expired',
                    'status' => 401
                ];
            }

            // Cek issuer (optional, sesuaikan dengan kebutuhan)
            if (isset($decoded->iss) && $decoded->iss !== 'backend-laravel') {
                return [
                    'valid' => false,
                    'message' => 'Token tidak valid',
                    'status' => 401
                ];
            }

            // Token valid
            return [
                'valid' => true,
                'decoded' => $decoded,
                'status' => 200
            ];
        } catch (\Firebase\JWT\ExpiredException $e) {
            return [
                'valid' => false,
                'message' => 'Token sudah expired',
                'status' => 401
            ];
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return [
                'valid' => false,
                'message' => 'Token tidak valid',
                'status' => 401
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Struktur token tidak valid',
                'status' => 401
            ];
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
