<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    private $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function signUp(Request $request)
    {
        $user = $this->authenticationService->signUp($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'User created successfully.',
                'clientMessage' => 'Usuário criado com sucesso.',
            ],
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function signIn(Request $request)
    {
        [$user, $token] = $this->authenticationService->signIn($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'User authenticated successfully.',
                'clientMessage' => 'Usuário autenticado com sucesso.',
            ],
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function signOut()
    {
        $this->authenticationService->signOut();

        return response()->json([
            'message' => [
                'serverMessage' => 'User logged out successfully.',
                'clientMessage' => 'Usuário deslogado com sucesso.',
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $this->authenticationService->forgotPassword($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'Password reset email sent successfully.',
                'clientMessage' => 'E-mail de redefinição de senha enviado com sucesso.',
            ],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $this->authenticationService->resetPassword($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'Password reset successfully.',
                'clientMessage' => 'Senha redefinida com sucesso.',
            ],
        ]);
    }
}
