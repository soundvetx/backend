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

    public function findAll(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('findAll'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('findAll'));
        }

        $authUser = Authentication::user();

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value) {
            throw new BaseException('ER002');
        }

        $filters = [];

        if (!empty($parameters['name'])) {
            $filters['name'] = $parameters['name'];
        }

        return $this->userRepository->findAll($parameters['page'], $parameters['limit'], $parameters['sortOrder'], $filters);
    }

    public function find(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('find'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('find'));
        }

        $authUser = Authentication::user();

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value && $authUser->id_user != $parameters['idUser']) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->find($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        return $user;
    }

    public function findMe()
    {
        return Authentication::user();
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

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value && $authUser->id_user != $parameters['id_user']) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->find($parameters['id_user']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        if (array_key_exists('email', $parameters)) {
            $existingUser = $this->userRepository->findByEmail($parameters['email']);

            if ($existingUser && $existingUser->id_user != $parameters['id_user']) {
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

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value && $authUser->id_user != $parameters['idUser']) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->find($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        $this->userRepository->delete([
            'id_user' => $parameters['idUser'],
            'updated_by' => $authUser->id_user,
        ]);

        $user->tokens()->delete();

        return true;
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

    public function canSendMessage(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('canSendMessage'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('canSendMessage'));
        }

        $authUser = Authentication::user();

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->find($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        return $this->userRepository->update([
            'id_user' => $parameters['idUser'],
            'can_send_message' => !$user->can_send_message,
            'updated_by' => $authUser->id_user,
        ]);
    }

    public function changePassword(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('changePassword'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('changePassword'));
        }

        $authUser = Authentication::user();

        if ($authUser->id_user != $parameters['idUser']) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->find($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        if (!Hash::check($parameters['currentPassword'], $user->password)) {
            throw new ValidationException('currentPassword', 'ER001', new ExceptionMessage([
                'server' => 'The current password is incorrect.',
                'client' => 'A senha atual está incorreta.',
            ]));
        }

        if ($parameters['newPassword'] !== $parameters['confirmNewPassword']) {
            throw new ValidationException('confirmNewPassword', 'ER001', new ExceptionMessage([
                'server' => 'The password confirmation does not match.',
                'client' => 'A confirmação da senha não coincide.',
            ]));
        }

        return $this->userRepository->update([
            'id_user' => $parameters['idUser'],
            'password' => Hash::make($parameters['newPassword']),
            'updated_by' => $authUser->id_user,
        ]);
    }

    public function resetPassword(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('resetPassword'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('resetPassword'));
        }

        $authUser = Authentication::user();

        if ($authUser->type === UserTypeEnum::VETERINARIAN->value) {
            throw new BaseException('ER002');
        }

        $user = $this->userRepository->find($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        $newPassword = Authentication::randomPassword();

        $user = $this->userRepository->update([
            'id_user' => $parameters['idUser'],
            'password' => Hash::make($newPassword),
            'updated_by' => $authUser->id_user,
        ]);

        return [$user, $newPassword];
    }

    public function updatePassword(array $parameters) {
        $validator = Validator::make($parameters, $this->getValidations('updatePassword'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('updatePassword'));
        }

        $user = $this->userRepository->find($parameters['idUser']);

        if (!$user) {
            throw new ResourceNotFoundException('user', new ExceptionMessage([
                'server' => 'User not found.',
                'client' => 'Usuário não encontrado.',
            ]));
        }

        return $this->userRepository->update([
            'id_user' => $parameters['idUser'],
            'password' => Hash::make($parameters['password']),
            'updated_by' => $parameters['idUser'],
        ]);
    }

    public function findByEmail(string $email)
    {
        return $this->userRepository->findByEmail($email);
    }

    private function getValidations(string $method)
    {
        return match ($method) {
            'findAll' => [
                'page' => [
                    'required',
                    'integer',
                ],
                'limit' => [
                    'required',
                    'integer',
                ],
                'sortOrder' => [
                    'required',
                    'in:asc,desc'
                ],
            ],
            'find' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
            ],
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
            'canSendMessage' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
            ],
            'changePassword' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
                'currentPassword' => [
                    'required',
                ],
                'newPassword' => [
                    'required',
                ],
                'confirmNewPassword' => [
                    'required',
                ],
            ],
            'resetPassword' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
            ],
            'updatePassword' => [
                'idUser' => [
                    'required',
                    'integer',
                ],
                'password' => [
                    'required',
                    'string',
                ],
            ],
            default => [],
        };
    }

    private function getErrorCodes(string $method)
    {
        return match ($method) {
            'findAll' => [
                'page.required' => 'ER001',
                'page.integer' => 'ER001',
                'limit.required' => 'ER001',
                'limit.integer' => 'ER001',
                'sortOrder.required' => 'ER001',
                'sortOrder.in' => 'ER001',
            ],
            'find' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
            ],
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
            'canSendMessage' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
            ],
            'changePassword' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
                'currentPassword.required' => 'ER001',
                'newPassword.required' => 'ER001',
                'confirmNewPassword.required' => 'ER001',
            ],
            'resetPassword' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
            ],
            'updatePassword' => [
                'idUser.required' => 'ER001',
                'idUser.integer' => 'ER001',
                'password.required' => 'ER001',
                'password.string' => 'ER001',
            ],
            default => [],
        };
    }
}
