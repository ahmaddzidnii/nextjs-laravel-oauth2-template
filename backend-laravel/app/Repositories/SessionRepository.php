<?php

namespace App\Repositories;

use App\Models\Session;

class SessionRepository
{
    public function createNewSession(string $userId, string $userAgent, string $refreshToken): Session
    {
        return Session::create([
            'user_id' => $userId,
            'user_agent' => $userAgent,
            'refresh_token' => $refreshToken,
            'last_login' => now()->timestamp,
            'is_active' => true,
            'ip' => request()->ip(), // Adding IP tracking
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function getActiveSessionsByUser(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Session::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
    }

    public function getSessionByUserAndAgent(string $userId, string $userAgent): ?Session
    {
        return Session::where('user_id', $userId)
            ->where('user_agent', $userAgent)
            ->where('is_active', true)
            ->first();
    }

    public function findActiveSessionByRefreshToken(string $refreshToken): ?Session
    {
        return Session::where('refresh_token', $refreshToken)
            ->where('is_active', true)
            ->first();
    }

    public function deleteSessionByRefreshToken(string $refreshToken)
    {
        return Session::where('refresh_token', $refreshToken)->delete();
    }
}
