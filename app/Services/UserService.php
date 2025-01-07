<?php

namespace App\Services;

use App\Enums\UserTypeEnum;
use App\Exceptions\BaseException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use App\Utils\Authentication;
use App\Utils\ExceptionMessage;
use App\Utils\Transformer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserService
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function create(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('create'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('create'));
        }

        $authUser = Authentication::user();

        if ($authUser && $authUser->type === UserTypeEnum::VETERINARIAN->value) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->findByEmail($parameters['email']);

        if ($user) {
            throw new ValidationException('email', 'ER001', new ExceptionMessage([
                'server' => 'The email has already been taken.',
                'client' => 'O e-mail já foi utilizado.',
            ]));
        }

        return $this->userRepository->create([
            'name' => $parameters['name'],
            'email' => $parameters['email'],
            'password' => Hash::make($parameters['password']),
            'type' => $parameters['type'],
            'crmv' => $parameters['crmv'] ?? null,
            'uf' => $parameters['uf'] ?? null,
            'created_by' => $authUser ? $authUser->id_user : null,
        ]);
    }

    public function update(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('update'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('update'));
        }

        $parameters = Transformer::camelToSnakeCase($parameters);
        $authUser = Authentication::user();

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value && $authUser->id_user !== $parameters['id_user']) {
            throw new BaseException('ER002');
        }

        if (array_key_exists('email', $parameters)) {
            $user = $this->userRepository->findByEmail($parameters['email']);

            if ($user && $user->id_user !== $parameters['id_user']) {
                throw new ValidationException('email', 'ER001', new ExceptionMessage([
                    'server' => 'The email has already been taken.',
                    'client' => 'O e-mail já foi utilizado.',
                ]));
            }
        }

        if (array_key_exists('password', $parameters)) {
            $parameters['password'] = Hash::make($parameters['password']);
        }

        if (array_key_exists('type', $parameters)) {
            $user = $this->userRepository->find($parameters['id_user']);

            if ($user->type !== UserTypeEnum::VETERINARIAN->value && $parameters['type'] === UserTypeEnum::VETERINARIAN->value) {
                if (!array_key_exists('crmv', $parameters)) {
                    throw new ValidationException('crmv', 'ER001', new ExceptionMessage([
                        'server' => 'The CRMV field is required for veterinarians.',
                        'client' => 'O campo CRMV é obrigatório para veterinários.',
                    ]));
                }

                if (!array_key_exists('uf', $parameters)) {
                    throw new ValidationException('uf', 'ER001', new ExceptionMessage([
                        'server' => 'The UF field is required for veterinarians.',
                        'client' => 'O campo UF é obrigatório para veterinários.',
                    ]));
                }
            }
        }

        if (array_key_exists('is_active', $parameters)) {
            unset($parameters['is_active']);
        }

        $parameters['updated_by'] = $authUser->id_user;

        return $this->userRepository->update($parameters);
    }

    public function delete(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('delete'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('delete'));
        }

        $authUser = Authentication::user();

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value && $authUser->id_user !== $parameters['idUser']) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->find($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        return $this->userRepository->delete([
            'id_user' => $parameters['idUser'],
            'updated_by' => $authUser->id_user,
        ]);
    }

    public function restore(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('restore'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('restore'));
        }

        $authUser = Authentication::user();

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->findRaw($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        return $this->userRepository->restore([
            'id_user' => $parameters['idUser'],
            'updated_by' => $authUser->id_user,
        ]);
    }

    public function findByEmail(string $email)
    {
        return $this->userRepository->findByEmail($email);
    }

    private function getValidations(string $method)
    {
        return match ($method) {
            'create' => [
                'type' => [
                    'required',
                    'in:' . implode(',', array_map(fn($type) => $type->value, UserTypeEnum::cases())),
                ],
                'name' => [
                    'required',
                ],
                'crmv' => [
                    'required_if:type,' . UserTypeEnum::VETERINARIAN->value,
                ],
                'uf' => [
                    'required_if:type,' . UserTypeEnum::VETERINARIAN->value,
                    'size:2',
                ],
                'email' => [
                    'required',
                    'email',
                ],
                'password' => [
                    'required',
                ],
            ],
            'update' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
                'type' => [
                    'nullable',
                    'in:' . implode(',', array_map(fn($type) => $type->value, UserTypeEnum::cases())),
                ],
                'name' => [
                    'nullable',
                ],
                'crmv' => [
                    'nullable',
                ],
                'uf' => [
                    'nullable',
                    'size:2',
                ],
                'email' => [
                    'nullable',
                    'email',
                ],
                'password' => [
                    'nullable',
                ],
            ],
            'delete' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
            ],
            'restore' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
            ],
            default => [],
        };
    }

    private function getErrorCodes(string $method)
    {
        return match ($method) {
            'create' => [
                'type.required' => 'ER001',
                'type.in' => 'ER001',
                'name.required' => 'ER001',
                'crmv.required_if' => 'ER001',
                'uf.required_if' => 'ER001',
                'uf.size' => 'ER001',
                'email.required' => 'ER001',
                'email.email' => 'ER001',
                'password.required' => 'ER001',
            ],
            'update' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
                'type.in' => 'ER001',
                'uf.size' => 'ER001',
                'email.email' => 'ER001',
            ],
            'delete' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
            ],
            'restore' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
            ],
            default => [],
        };
    }
}
