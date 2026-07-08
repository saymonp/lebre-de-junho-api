<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\User\UserService;
use App\Http\Requests\Api\AssignPermissionsRequest;
use App\Http\Requests\Api\AssignRolesRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
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
}
