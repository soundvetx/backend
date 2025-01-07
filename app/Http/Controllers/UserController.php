<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function create(Request $request)
    {
        $user = $this->userService->create($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'User created successfully.',
                'clientMessage' => 'Usuário criado com sucesso.',
            ],
            'user' => new UserResource($user),
        ]);
    }

    public function update(Request $request, $idUser)
    {
        $request->merge(['idUser' => $idUser]);
        $user = $this->userService->update($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'User updated successfully.',
                'clientMessage' => 'Usuário atualizado com sucesso.',
            ],
            'user' => new UserResource($user),
        ]);
    }

    public function delete($idUser)
    {
        $this->userService->delete($idUser);

        return response()->json([
            'message' => [
                'serverMessage' => 'User deleted successfully.',
                'clientMessage' => 'Usuário deletado com sucesso.',
            ],
        ]);
    }

    public function restore($idUser)
    {
        $this->userService->restore($idUser);

        return response()->json([
            'message' => [
                'serverMessage' => 'User restored successfully.',
                'clientMessage' => 'Usuário restaurado com sucesso.',
            ],
        ]);
    }
}
