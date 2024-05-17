<?php

namespace Mongo\Auth;

use App\Collections\UserCollection;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class MongoUserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    public function retrieveById($identifier)
    {
        return UserCollection::findById($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        return UserCollection::findOne([
            '_id' => $identifier,
            'remember_token' => $token,
        ]);
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        UserCollection::updateOne([
            '_id' => $user->getAuthIdentifier(),
        ], [
            '$set' => [
                $user->getRememberTokenName() => $token,
            ],
        ]);
    }

    public function retrieveByCredentials(array $credentials)
    {
        return UserCollection::findOne([
            'email' => $credentials['email'],
        ]);
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        if (Hash::needsRehash($user->getAuthPassword()) || $force) {
            UserCollection::updateOne([
                '_id' => $user->getAuthIdentifier(),
            ], [
                '$set' => [
                    $user->getAuthPasswordName() => Hash::make($credentials['password']),
                ],
            ]);
        }
    }
}
