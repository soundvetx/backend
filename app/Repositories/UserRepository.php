<?php

namespace App\Repositories;

use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function create(array $parameters): User
    {
        DB::beginTransaction();

        $user = User::create($parameters);

        $user->update([
            'created_by' => $user->id_user,
            'updated_by' => $user->id_user,
        ]);

        if ($parameters['type'] === UserTypeEnum::VETERINARIAN->value) {
            $user->veterinarian()->create([
                'crmv' => $parameters['crmv'],
                'uf' => $parameters['uf'],
                'created_by' => $user->id_user,
                'updated_by' => $user->id_user,
            ]);
        }

        DB::commit();

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
