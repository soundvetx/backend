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
            'user' => new UserResource($user),
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
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }
}
