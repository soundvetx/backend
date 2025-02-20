<?php

namespace App\Repositories;

use App\Enums\UserTypeEnum;
use App\Exceptions\BaseException;
use App\Models\User;
use App\Utils\ExceptionMessage;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function create(array $parameters): User
    {
        try {
            DB::beginTransaction();

            $user = User::create($parameters);

            $user->update([
                'created_by' => $parameters['created_by'] ?? $user->id_user,
                'updated_by' => $parameters['created_by'] ?? $user->id_user,
            ]);

            if ($parameters['type'] === UserTypeEnum::VETERINARIAN->value) {
                $user->veterinarian()->create([
                    'crmv' => $parameters['crmv'],
                    'uf' => $parameters['uf'],
                    'created_by' => $parameters['created_by'] ?? $user->id_user,
                    'updated_by' => $parameters['created_by'] ?? $user->id_user,
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new BaseException('ER000', new ExceptionMessage([
                'server' => $e->getMessage(),
                'client' => 'Ocorreu um erro ao criar o usuário.',
            ]));
        }

        $user->refresh();
        return $user;
    }

    public function update(array $parameters): User
    {
        try {
            DB::beginTransaction();

            $user = $this->find($parameters['id_user']);

            $user->update(
                array_merge($parameters, [
                    'updated_by' => $parameters['updated_by'],
                ])
            );

            if (array_key_exists('type', $parameters)) {
                if ($parameters['type'] === UserTypeEnum::VETERINARIAN->value) {
                    if (!$user->veterinarian) {
                        $user->veterinarian()->create([
                            'crmv' => $parameters['crmv'],
                            'uf' => $parameters['uf'],
                            'created_by' => $parameters['updated_by'],
                            'updated_by' => $parameters['updated_by'],
                        ]);
                    } else {
                        $user->veterinarian()->update([
                            'crmv' => $parameters['crmv'] ?? $user->veterinarian->crmv,
                            'uf' => $parameters['uf'] ?? $user->veterinarian->uf,
                            'updated_by' => $parameters['updated_by'],
                        ]);
                    }
                } else if ($user->veterinarian) {
                    $user->veterinarian->delete();
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new BaseException('ER000', new ExceptionMessage([
                'server' => $e->getMessage(),
                'client' => 'Ocorreu um erro ao atualizar o usuário.',
            ]));
        }

        $user->refresh();
        return $user;
    }

    public function delete(array $parameters): bool
    {
        $user = $this->find($parameters['id_user']);

        return $user->update([
            'is_active' => 0,
            'updated_by' => $parameters['updated_by'],
        ]);
    }

    public function restore(array $parameters): bool
    {
        $user = $this->findRaw($parameters['id_user']);

        return $user->update([
            'is_active' => 1,
            'updated_by' => $parameters['updated_by'],
        ]);
    }

    public function find(int $idUser): ?User
    {
        return User::where('is_active', 1)
            ->find($idUser);
    }

    public function findRaw(int $idUser): ?User
    {
        return User::find($idUser);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findAll($page, $limit, $sortOrder, array $filters): Collection
    {
        $query = User::where('is_active', 1);

        if (array_key_exists('name', $filters)) {
            $query = $query
                ->whereRaw("LOWER(`name`) COLLATE utf8mb4_general_ci LIKE ?", ['%' . strtolower($filters['name']) . '%']);
        }

        $query = $query
            ->orderBy('name', $sortOrder)
            ->offset(($limit * $page ) + ($page > 0 ? 1 : 0))
            ->limit($limit + 1);

        return $query->get();
    }

    public function findAllByName(string $name): Collection
    {
        return User::where('is_active', 1)
            ->whereRaw("LOWER(`name`) COLLATE utf8mb4_general_ci LIKE ?", ['%' . strtolower($name) . '%'])
            ->get();
    }

    public function findVeterinarianByCrmv(string $crmv, string $uf): ?User
    {
        return User::where('is_active', 1)
            ->where('type', UserTypeEnum::VETERINARIAN->value)
            ->whereHas('veterinarian', function ($query) use ($crmv, $uf) {
                $query->where('crmv', $crmv)
                    ->where('uf', $uf);
            })
            ->first();
    }
}
