<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * Create or update a user based on Google API login.
     *
     * @param  $googleUser
     * @return User
     */
    public function createOrUpdateUserGoogle($googleUser)
    {
        $user = User::where('provider_id', $googleUser['sub'])->first();

        if ($user) {
            // Update existing user
            $user->username = $googleUser['name'];
            $user->email = $googleUser['email'];
            $user->provider_id = $googleUser['sub'];
            $user->avatar = $googleUser['picture'];
            $user->save();
        } else {
            // Create new user
            $user = User::create([
                'username' => $googleUser['name'],
                'email' => $googleUser['email'],
                'provider_id' => $googleUser['sub'],
                'avatar' => $googleUser['picture'],
            ]);
        }

        return $user;
    }
}