<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\User;
use App\Utils\Authentication;
use App\Utils\ExceptionMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthenticationService
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

        return $this->userService->create($parameters);
    }

    public function signIn(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('signIn'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('signIn'));
        }

        $user = $this->userService->findByEmail($parameters['email']);

        if (!$user || !Hash::check($parameters['password'], $user->password)) {
            throw new ValidationException('email_password', 'ER001', new ExceptionMessage([
                'server' => 'The provided credentials are incorrect.',
                'client' => 'As credenciais fornecidas estão incorretas.',
            ]));
        }

        $tokenDetails = [
            'name' => 'authentication',
            'abilities' => ['*'],
            'expires_at' => now()->addDays(1),
        ];

        $token = $user->createToken(
            $tokenDetails['name'],
            $tokenDetails['abilities'],
            $tokenDetails['expires_at'],
        )->plainTextToken;

        return [$user, $token];
    }

    public function signOut()
    {
        $user = Authentication::user();

        if (!$user) {
            return false;
        }

        return $user->tokens()
            ->where('id_personal_access_token', $user->currentAccessToken()->id_personal_access_token)
            ->delete();
    }

    private function getValidations(string $method)
    {
        return match ($method) {
            'signUp' => [
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
