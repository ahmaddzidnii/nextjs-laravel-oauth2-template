<?php

namespace App\Repositories;

use App\Models\Account;
use App\ProviderEnum;

class AccountRepository
{
    public function createAccount(string $user_id, ProviderEnum $provider, string $providerId, string $refreshToken, int $expiresIn): Account
    {
        return Account::create([
            'user_id' => $user_id,
            'provider' => $provider,
            'provider_id' => $providerId,
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addSeconds($expiresIn)
        ]);
    }

    public function findByProviderId(string $providerId, string $provider): ?Account
    {
        return Account::where('provider_id', $providerId)
            ->where('provider', $provider)
            ->first();
    }

    public function getAccountsByUserId(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Account::where('user_id', $userId)
            ->get();
    }

    public function findOrCreateAccount(ProviderEnum $provider, string $userId, string $providerId): Account
    {
        return Account::firstOrCreate(
            [
                'user_id' => $userId,
                'provider' => $provider,
                'provider_id' => $providerId
            ],
        );
    }
}
