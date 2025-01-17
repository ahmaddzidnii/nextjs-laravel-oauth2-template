<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleOAuthHelper
{
    public static function exchangeCode($code)
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


    public static function getUserInfo($access_token)
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
}
