<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Profile\UserService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\Api\AssignPermissionsRequest;
use App\Http\Requests\Api\AssignRolesRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
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

    public function listPermissions(): JsonResponse
    {
        return response()->json(\Spatie\Permission\Models\Permission::all());
    }

    public function listRoles()
    {
        return response()->json(Role::all());
    }

    /**
     * Atribuir permissões diretas ao usuário
     */
    public function assignPermissions(AssignPermissionsRequest $request, User $user): JsonResponse
    {
        // 1. O request já foi validado. Chamamos o service passando o model e o array de dados.
        $updatedUser = $this->service->syncPermissions($user, $request->input('permissions'));

        // 2. Retorna a resposta padronizada com o UserResource carregando as novas relações
        return response()->json([
            'message' => "Permissões diretas atualizadas com sucesso para {$updatedUser->name}.",
            'user'    => new UserResource($updatedUser->load(['roles', 'permissions'])),
        ], 200);
    }

    /**
     * Atribui uma ou mais roles a um usuário específico.
     */
    public function assignRole(AssignRolesRequest $request, User $user): JsonResponse
    {
        // 1. Executa a sincronização chamando o service com os dados validados do request
        $updatedUser = $this->service->syncRoles($user, $request->input('roles'));

        // 2. Retorna a resposta JSON no padrão ouro usando o Resource
        return response()->json([
            'message' => "Roles atualizadas com sucesso para o usuário {$updatedUser->name}.",
            'user'    => new UserResource($updatedUser->load(['roles', 'permissions'])),
        ], 200);
    }

    /**
     * Exibe a listagem de usuários para o painel administrativo.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // 1. Opcional: Permite que o Nuxt envie um ?per_page=30 para mudar a quantidade na tela
        $perPage = $request->query('per_page', 15);

        // 2. Busca a paginação através do Service
        $users = $this->service->listUsers((int) $perPage);

        // 3. Devolve a coleção inteira limpa, formatada e com os metadados do paginator
        return UserResource::collection($users);
    }
}
