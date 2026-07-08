<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Profile\UserService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * Registro de usuário
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // O FormRequest já validou os dados
        $user = $this->service->register($request->validated());

        return response()->json([
            'message' => 'Cadastro realizado com sucesso! Por favor, verifique sua caixa de entrada para confirmar seu e-mail.',
            'user'    => new UserResource($user)
        ], 201);
    }

    /**
     * Endpoint para o frontend enviar os dados assim que a página /verify-email carregar
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        // O service processa a validação. Se falhar, a ValidationException assume o erro 422.
        $user = $this->service->verifyEmail($data['token'], $data['email']);

        // Já loga o usuário automaticamente após confirmar o e-mail!
        $apiToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'E-mail verificado com sucesso! Bem-vindo à loja.',
            'access_token' => $apiToken,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user->load(['roles', 'permissions'])),
        ], 200);
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
            'user' => new UserResource($user->load(['roles', 'permissions'])),
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
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'        => 'required|email',
            'token'        => 'required|string',
            'new_password' => 'required'
        ]);

        $user = $this->service->resetPassword($data['token'], $data['email'], $data['new_password']);

        $apiToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $apiToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load(['roles', 'permissions'])),
        ], 200);
    }
}
