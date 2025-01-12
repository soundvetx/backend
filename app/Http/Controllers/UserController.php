<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function findAll(Request $request)
    {
        $users = $this->userService->findAll([
            'name' => $request->query('name')
        ]);

        return response()->json([
            'message' => [
                'serverMessage' => 'Users retrieved successfully.',
                'clientMessage' => 'Usuários recuperados com sucesso.',
            ],
            'data' => [
                'users' => UserResource::collection($users),
            ],
        ]);
    }

    public function find($idUser)
    {
        $user = $this->userService->find([
            'idUser' => $idUser,
        ]);

        return response()->json([
            'message' => [
                'serverMessage' => 'User retrieved successfully.',
                'clientMessage' => 'Usuário recuperado com sucesso.',
            ],
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function findMe()
    {
        $user = $this->userService->findMe();

        return response()->json([
            'message' => [
                'serverMessage' => 'User retrieved successfully.',
                'clientMessage' => 'Usuário recuperado com sucesso.',
            ],
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->userService->create($request->all());

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

    public function update(Request $request, $idUser)
    {
        $request->merge(['idUser' => $idUser]);
        $user = $this->userService->update($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'User updated successfully.',
                'clientMessage' => 'Usuário atualizado com sucesso.',
            ],
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function delete($idUser)
    {
        $this->userService->delete([
            'idUser' => $idUser,
        ]);

        return response()->json([
            'message' => [
                'serverMessage' => 'User deleted successfully.',
                'clientMessage' => 'Usuário deletado com sucesso.',
            ],
        ]);
    }

    public function restore($idUser)
    {
        $this->userService->restore([
            'idUser' => $idUser,
        ]);

        return response()->json([
            'message' => [
                'serverMessage' => 'User restored successfully.',
                'clientMessage' => 'Usuário restaurado com sucesso.',
            ],
        ]);
    }

    public function canSendWhatsapp($idUser)
    {
        $this->userService->canSendWhatsapp([
            'idUser' => $idUser,
        ]);

        return response()->json([
            'message' => [
                'serverMessage' => 'User updated successfully.',
                'clientMessage' => 'Usuário atualizado com sucesso.',
            ],
        ]);
    }

    public function changePassword(Request $request, $idUser)
    {
        $request->merge(['idUser' => $idUser]);
        $this->userService->changePassword($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'Password changed successfully.',
                'clientMessage' => 'Senha alterada com sucesso.',
            ],
        ]);
    }

    public function resetPassword($idUser)
    {
        [, $newPassword] = $this->userService->resetPassword([
            'idUser' => $idUser,
        ]);

        return response()->json([
            'message' => [
                'serverMessage' => 'Password reset successfully.',
                'clientMessage' => 'Senha resetada com sucesso.',
            ],
            'data' => [
                'newPassword' => $newPassword,
            ],
        ]);
    }
}
