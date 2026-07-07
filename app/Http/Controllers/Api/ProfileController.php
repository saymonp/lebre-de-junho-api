<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Services\Profile\ProfileService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    protected $service;

    public function __construct(ProfileService $service)
    {
        $this->service = $service;
    }

    public function getProfile(Request $request)
    {
        return new ProfileResource(
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

    public function register(RegisterRequest $request): JsonResponse
    {
        // 1. O FormRequest já garantiu que os termos foram aceitos e o email é único.
        // Pegamos apenas os dados limpos e validados.
        $user = $this->service->register($request->validated());

        // 2. Criação do token via Laravel Sanctum
        $apiToken = $user->createToken('auth_token')->plainTextToken;

        // 3. Resposta padronizada enviando o token + o Resource do Usuário com as Roles
        return response()->json([
            'access_token' => $apiToken,
            'token_type' => 'Bearer',
            // Carrega dinamicamente a relação para o UserResource mapear as permissões
            'user' => new ProfileResource($user->load(['roles', 'permissions'])),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // 1. Aciona o service passando os dados validados do request
        // Se falhar, a exceção para o código aqui e responde ao Nuxt automaticamente
        $user = $this->service->login($request->validated());

        // 2. Emite o token de autenticação via Sanctum
        $apiToken = $user->createToken('auth_token')->plainTextToken;

        // 3. Retorna a resposta de sucesso com o UserResource otimizado
        return response()->json([
            'access_token' => $apiToken,
            'token_type' => 'Bearer',
            'user' => new ProfileResource($user->load(['roles', 'permissions'])),
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoga apenas o token que foi usado para autenticar a requisição atual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado'
        ], 200);
    }
    /**
     * Admin deleta um usuário específico
     */
    public function destroy(User $user): JsonResponse
    {
        // O Laravel já buscou o usuário pelo ID da URL automaticamente.
        // Se não existisse, a requisição morria antes com erro 404.

        $this->service->destroy($user);

        return response()->json([
            'message' => 'Usuário excluído com sucesso pelo administrador.'
        ], 200);
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

    /**
     * Solicitar Recuperar Senha
     */
    public function recoverPassword(Request $request): JsonResponse
    {
        // Validação rápida para garantir que é um e-mail estruturado
        $request->validate([
            'email' => 'required|email'
        ]);

        $this->service->recoverPassword($request->input('email'));

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, um link de redefinição será enviado.'
        ], 200);
    }

    /**
     * Resetar a Senha
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email'        => 'required|email',
            'token'        => 'required|string',
            'new_password' => 'required',
            'string',
        ]);

        $user = $this->service->resetPassword($data['token'], $data['email'], $data['new_password']);

        $apiToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $apiToken,
            'token_type' => 'Bearer',
            'user' => new ProfileResource($user->load(['roles', 'permissions'])),
        ], 200);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }
}
