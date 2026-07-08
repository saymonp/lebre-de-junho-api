<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Profile\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ProfileController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function getProfile(Request $request)
    {
        return new UserResource(
            $this->service->getProfile($request->user())
        );
    }
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:new_password',
            'new_password'     => 'required_with:current_password|confirmed|min:8',
        ]);

        try {
            $this->service->updateProfile($user, $validated);

            return response()->json([
                'message' => 'Profile updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * O próprio usuário deleta sua conta
     */
    public function deleteOwnAccount(Request $request): JsonResponse
    {
        // Captura o usuário logado diretamente do token do Sanctum
        $user = $request->user();

        // Passa a instância segura para o Service
        $this->service->deleteOwnAccount($user);

        return response()->json([
            'message' => 'Sua conta foi excluída com sucesso.'
        ], 200);
    }
}
