<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findOrCreateByGoogleUser(array $userInfo): User
    {
        return User::firstOrCreate(
            [
                'provider_id' => $userInfo['id'],
                'provider' => 'google'
            ],
            [
                'name' => $userInfo['name'],
                'email' => $userInfo['email'],
                'avatar' => $userInfo['picture'],
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function createUser(array $data): User
    {
        return User::create($data);
    }
}
