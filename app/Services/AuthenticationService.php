<?php

namespace App\Services;

use App\Enums\UserTypeEnum;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use App\Utils\ExceptionMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthenticationService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function signUp(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('signUp'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('signUp'));
        }

        if ($parameters['password'] !== $parameters['confirmPassword']) {
            throw new ValidationException('password', 'ER001', new ExceptionMessage([
                'server' => 'The password confirmation does not match.',
                'client' => 'As senhas não coincidem.',
            ]));
        }

        $user = $this->userRepository->create([
            'name' => $parameters['name'],
            'email' => $parameters['email'],
            'password' => Hash::make($parameters['password']),
            'type' => $parameters['type'],
            'crmv' => $parameters['crmv'] ?? null,
            'uf' => $parameters['uf'] ?? null,
        ]);

        // $tokenDetails = [
        //     'name' => 'authentication',
        //     'abilities' => ['*'],
        //     'expires_at' => now()->addDays(1),
        // ];

        // $token = $user->createToken(
        //     $tokenDetails['name'],
        //     $tokenDetails['abilities'],
        //     $tokenDetails['expires_at'],
        // )->plainTextToken;

        return $user;
    }

    private function getValidations(string $method)
    {
        return match ($method) {
            'signUp' => [
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
                'confirmPassword' => [
                    'required',
                ],
            ],
            'signIn' => [
                'email' => [
                    'required',
                    'email',
                ],
                'password' => [
                    'required',
                ],
            ],
            default => [],
        };
    }

    private function getErrorCodes(string $method)
    {
        return match ($method) {
            'signUp' => [
                'type.required' => 'ER001',
                'type.in' => 'ER001',
                'name.required' => 'ER001',
                'crmv.required_if' => 'ER001',
                'uf.required_if' => 'ER001',
                'uf.size' => 'ER001',
                'email.required' => 'ER001',
                'email.email' => 'ER001',
                'password.required' => 'ER001',
                'confirmPassword.required' => 'ER001',
            ],
            'signIn' => [
                'email.required' => 'ER001',
                'email.email' => 'ER001',
                'password.required' => 'ER001',
            ],
            default => [],
        };
    }
}
