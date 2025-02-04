<?php

namespace App\Repositories;

use App\Models\PersonalAccessToken;
use App\Models\User;

class PersonalAccessTokenRepository
{
    public function create(User $user, array $parameters): string
    {
        return $user->createToken(
            $parameters['name'],
            $parameters['abilities'],
            $parameters['expires_at'],
        )->plainTextToken;
    }

    public function delete(int $idPersonalAccessToken): bool
    {
        $personalAccessToken = $this->find($idPersonalAccessToken);

        if (!$personalAccessToken) {
            return true;
        }

        return $personalAccessToken->delete();
    }

    public function find(int $idPersonalAccessToken): ?PersonalAccessToken
    {
        return PersonalAccessToken::where('id_personal_access_token', $idPersonalAccessToken)->first();
    }

    public function findByToken(string $token): ?PersonalAccessToken
    {
        return PersonalAccessToken::findToken($token);
    }
}
