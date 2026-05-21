<?php

namespace App\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class JwtPayloadUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        return new GenericUser(['id' => $identifier]);
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // Stateless: no remember token support.
    }

    public function retrieveByCredentials(array $credentials)
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Stateless: no password rehash support.
    }
}
