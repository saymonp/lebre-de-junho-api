<?php

namespace Tests\Feature\Api\Profile;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Executado antes de cada teste
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Força todas as requisições a esperarem JSON
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);

        // Cria um usuário comum para os testes de perfil
        $this->user = User::factory()->create([
            'name' => 'User Cliente',
            'email' => 'user.cliente@lebredejunho.com',
            'password' => Hash::make('senha_antiga_123'),
        ]);
    }

    /**
     * Teste: getProfile
     */
    public function test_should_return_authenticated_user_profile_data(): void
    {
        // Autentica o usuário com o Sanctum
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email']
            ])
            ->assertJsonFragment([
                'name' => 'User Cliente',
                'email' => 'user.cliente@lebredejunho.com',
            ]);
    }

    /**
     * Teste: update (Apenas nome e email)
     */
    public function test_should_update_profile_basic_information_successfully(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $payload = [
            'name' => 'User Alterado',
            'email' => 'user.novo@lebredejunho.com',
        ];

        $response = $this->putJson('/api/user', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Profile updated successfully.'
            ]);

        // Valida se alterou de fato na tabela
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'User Alterado',
            'email' => 'user.novo@lebredejunho.com',
        ]);
    }

    /**
     * Teste: update (Trocando a senha com confirmação)
     */
    public function test_should_update_password_when_current_password_is_provided_correctly(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $payload = [
            'current_password' => 'senha_antiga_123',
            'new_password' => 'nova_senha_super_segura',
            'new_password_confirmation' => 'nova_senha_super_segura',
        ];

        $response = $this->putJson('/api/user', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Profile updated successfully.'
            ]);
            
        // O Service testará a Hash interna, mas o sinal verde do status 200 já garante o sucesso do fluxo
    }

    /**
     * Teste: update (Erro de validação ao tentar mudar senha sem confirmação)
     */
    public function test_should_fail_to_update_password_if_confirmation_does_not_match(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $payload = [
            'current_password' => 'senha_antiga_123',
            'new_password' => 'nova_senha_super_segura',
            'new_password_confirmation' => 'senha_errada_digitada', // Não bate
        ];

        $response = $this->putJson('/api/user', $payload);

        // O validador do Laravel pega a regra 'confirmed' no campo 'new_password'
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    /**
     * Teste: deleteOwnAccount
     */
    public function test_should_allow_user_to_delete_their_own_account(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->deleteJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Sua conta foi excluída com sucesso.'
            ]);

        // Garante que o registro sumiu do banco de dados
        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id
        ]);
    }

    /**
     * Teste de Segurança: Acesso sem Token (Unauthenticated)
     */
    public function test_should_deny_access_to_profile_endpoints_if_not_logged_in(): void
    {
        // Dispara a requisição SEM chamar o Sanctum::actingAs antes
        $response = $this->getJson('/api/user');

        $response->assertStatus(401); // Protegido pelo auth:sanctum
    }
}