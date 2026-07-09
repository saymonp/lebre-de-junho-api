<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /**
     * Configuração inicial executada antes de cada teste.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Alimenta as Roles e Permissions necessárias para os testes no guard correto (api)
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        Role::create(['name' => 'user', 'guard_name' => 'api']);
        Role::create(['name' => 'editor', 'guard_name' => 'api']);
        
        $deletePermission = Permission::create(['name' => 'delete users', 'guard_name' => 'api']);
        Permission::create(['name' => 'edit articles', 'guard_name' => 'api']);

        // Dá o poder de exclusão para a Role de admin
        $adminRole->givePermissionTo($deletePermission);

        // 2. Cria o usuário administrador e atrela a Role
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /**
     * Teste: listPermissions
     */
    public function test_should_list_all_permissions(): void
    {
        // Autentica como Admin usando o Sanctum
    
        $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/permissions');

        $response->assertStatus(200)
            ->assertJsonCount(2) // Criamos 'delete users' e 'edit articles' no setUp
            ->assertJsonFragment(['name' => 'delete users'])
            ->assertJsonFragment(['name' => 'edit articles']);
    }

    /**
     * Teste: listRoles
     */
    public function test_should_list_all_roles(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');
        $response = $this->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJsonCount(3) // admin, user, editor
            ->assertJsonFragment(['name' => 'admin'])
            ->assertJsonFragment(['name' => 'user']);
    }

    /**
     * Teste: index (listagem paginada de usuários)
     */
    public function test_should_list_paginated_users(): void
    {
        // Cria mais 3 usuários comuns no banco
        User::factory()->count(3)->create();

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        // Testa passando o parâmetro ?per_page=2 para testar a paginação
        $response = $this->getJson('/api/users?per_page=2');

        // Como o Laravel Resource Paginated envelopa em 'data' e cria links/meta:
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email']
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total']
            ]);
            
        // Validando se o limite imposto funcionou (total de 4 usuários no banco, mas mostra 2 por página)
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Teste: assignPermissions
     */
    public function test_should_sync_direct_permissions_to_a_user(): void
    {
        $targetUser = User::factory()->create();

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $payload = [
            'permissions' => ['edit articles']
        ];

        $response = $this->postJson("/api/users/{$targetUser->id}/permissions", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "Permissões diretas atualizadas com sucesso para {$targetUser->name}."
            ]);

        // Verifica se a permissão foi gravada diretamente para o usuário alvo
        $this->assertTrue($targetUser->fresh()->hasDirectPermission('edit articles'));
    }

    /**
     * Teste: assignRole
     */
    public function test_should_sync_roles_to_a_user(): void
    {
        $targetUser = User::factory()->create();

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $payload = [
            'roles' => ['editor']
        ];

        $response = $this->postJson("/api/users/{$targetUser->id}/roles", $payload);
        dump($response);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "Roles atualizadas com sucesso para o usuário {$targetUser->name}."
            ]);

        // Verifica se o usuário agora possui o papel de editor
        $this->assertTrue($targetUser->fresh()->hasRole('editor'));
    }

    /**
     * Teste: destroy
     */
    public function test_should_delete_user_if_admin_has_delete_permission(): void
    {
        $targetUser = User::factory()->create();

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->deleteJson("/api/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Usuário excluído com sucesso pelo administrador.'
            ]);

        // Garante que o registro foi removido da tabela users
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id
        ]);
    }

    /**
     * Teste de Segurança: destroy sem permissão específica (Barrado pelo Middleware)
     */
    public function test_should_not_allow_deleting_user_if_admin_lacks_delete_permission(): void
    {
        // Cria um admin de testes capado (sem a permissão de exclusão)
        $limitedAdmin = User::factory()->create();
        $limitedAdmin->assignRole('admin');
        
        // Remove explicitamente a permissão dele/da role dele para esse teste
        Role::findByName('admin', 'api')->revokePermissionTo('delete users');

        Sanctum::actingAs($limitedAdmin, ['*'], 'sanctum');

        $targetUser = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$targetUser->id}");

        // O middleware 'permission:delete users' joga um erro 403 (Forbidden)
        $response->assertStatus(403);

        // O usuário NÃO pode ter sido deletado
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id
        ]);
    }
}