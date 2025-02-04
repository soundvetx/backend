<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Repositories\PersonalAccessTokenRepository;
use App\Utils\Authentication;
use App\Utils\ExceptionMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthenticationService
{
    private $personalAccessTokenRepository;
    private $userService;

    public function __construct(UserService $userService, PersonalAccessTokenRepository $personalAccessTokenRepository)
    {
        $this->userService = $userService;
        $this->personalAccessTokenRepository = $personalAccessTokenRepository;
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

    private function createAuthenticationToken(User $user)
    {
        $tokenDetails = [
            'name' => 'authentication',
            'abilities' => ['*'],
            'expires_at' => now()->addDays(1),
        ];

        return $this->personalAccessTokenRepository->create($user, $tokenDetails);
    }

    private function createRefreshToken(User $user)
    {
        $refreshTokenDetails = [
            'name' => 'refresh_token',
            'abilities' => ['*'],
            'expires_at' => now()->addDays(2),
        ];

        return $this->personalAccessTokenRepository->create($user, $refreshTokenDetails);
    }

    private function createPasswordResetToken(User $user)
    {
        $tokenDetails = [
            'name' => 'password_reset',
            'abilities' => ['*'],
            'expires_at' => now()->addMinutes(15),
        ];

        return $this->personalAccessTokenRepository->create($user, $tokenDetails);
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

        $token = $this->createAuthenticationToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return [$user, $token, $refreshToken];
    }

    public function signOut()
    {
        $user = Authentication::user();

        if (!$user) {
            return false;
        }

        return $this->personalAccessTokenRepository->delete($user->currentAccessToken()->id_personal_access_token);
    }

    public function forgotPassword(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('forgotPassword'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('forgotPassword'));
        }

        $user = $this->userService->findByEmail($parameters['email']);

        if (!$user) {
            throw new ValidationException('email', 'ER001', new ExceptionMessage([
                'server' => 'The provided e-mail does not exist in our database.',
                'client' => 'O e-mail informado não consta em nossa base de dados.',
            ]));
        }

        [, $token] = explode('|', $this->createPasswordResetToken($user), 2);

        Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

        return true;
    }

    public function resetPassword(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('resetPassword'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('resetPassword'));
        }

        $resetPasswordToken = $this->personalAccessTokenRepository->findByToken($parameters['token']);

        if (!$resetPasswordToken || $resetPasswordToken->name !== 'password_reset' || $resetPasswordToken->expires_at < now()) {
            throw new ValidationException('token', 'ER001', new ExceptionMessage([
                'server' => 'The provided token is invalid or expired.',
                'client' => 'O token fornecido é inválido ou expirou.',
            ]));
        }

        if ($parameters['newPassword'] !== $parameters['confirmNewPassword']) {
            throw new ValidationException('newPassword', 'ER001', new ExceptionMessage([
                'server' => 'The password confirmation does not match.',
                'client' => 'As senhas não coincidem.',
            ]));
        }

        $this->userService->updatePassword([
            'idUser' => $resetPasswordToken->tokenable->id_user,
            'password' => $parameters['newPassword'],
        ]);

        $this->personalAccessTokenRepository->delete($resetPasswordToken->id_personal_access_token);

        return true;
    }

    public function refreshToken()
    {
        $user = Authentication::user();

        $token = $this->createAuthenticationToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return [$token, $refreshToken];
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
            'forgotPassword' => [
                'email' => [
                    'required',
                    'email',
                ],
            ],
            'resetPassword' => [
                'token' => [
                    'required',
                    'string',
                ],
                'newPassword' => [
                    'required',
                    'string',
                ],
                'confirmNewPassword' => [
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
            'signUp' => [
                'password.required' => 'ER001',
                'confirmPassword.required' => 'ER001',
            ],
            'signIn' => [
                'email.required' => 'ER001',
                'email.email' => 'ER001',
                'password.required' => 'ER001',
            ],
            'forgotPassword' => [
                'email.required' => 'ER001',
                'email.email' => 'ER001',
            ],
            'resetPassword' => [
                'token.required' => 'ER001',
                'token.string' => 'ER001',
                'newPassword.required' => 'ER001',
                'newPassword.string' => 'ER001',
                'confirmNewPassword.required' => 'ER001',
                'confirmNewPassword.string' => 'ER001',
            ],
            default => [],
        };
    }
}
