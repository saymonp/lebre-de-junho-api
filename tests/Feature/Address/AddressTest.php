<?php

namespace Tests\Feature\Api\Address;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Configuração inicial para cada teste
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Força o cabeçalho de todas as requisições para JSON
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);

        // Cria o usuário dono dos endereços do teste
        $this->user = User::factory()->create();
    }

    /**
     * Teste: index (Listar endereços com paginação e padrão no topo)
     */
    public function test_should_list_paginated_addresses_with_default_on_top(): void
    {
        Sanctum::actingAs($this->user);

        // Cria 2 endereços comuns e 1 padrão para o usuário
        Address::factory()->create(['user_id' => $this->user->id, 'titulo' => 'Comum 1', 'padrao' => false]);
        Address::factory()->create(['user_id' => $this->user->id, 'titulo' => 'Padrão', 'padrao' => true]);
        Address::factory()->create(['user_id' => $this->user->id, 'titulo' => 'Comum 2', 'padrao' => false]);

        $response = $this->getJson('/api/addresses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => ['id', 'titulo', 'logradouro', 'padrao']
                ],
                'total'
            ]);

        // Garante que o primeiro item da lista ('data.0') é o endereço padrão (True)
        $this->assertTrue($response->json('data.0.padrao'));
        $this->assertEquals('Padrão', $response->json('data.0.titulo'));
    }

    /**
     * Teste: store (Criar endereço com sucesso)
     */
    public function test_should_create_an_address_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'titulo' => 'Minha Casa',
            'destinatario' => 'User',
            'telefone' => '51999998888',
            'cep' => '93000000',
            'logradouro' => 'Rua das Lebres',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'São Martinho',
            'estado' => 'RS',
            'complemento' => 'Ap 202',
            'padrao' => true
        ];

        $response = $this->postJson('/api/addresses', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['titulo' => 'Minha Casa']);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $this->user->id,
            'titulo' => 'Minha Casa',
            'padrao' => true
        ]);
    }

    /**
     * Teste: Regra de Negócio do Endereço Padrão Único
     */
    public function test_should_reset_old_default_address_when_a_new_one_is_set_as_default(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Já temos um endereço padrão antigo salvo
        $oldAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'padrao' => true
        ]);

        // 2. Cria um novo endereço via API também marcado como padrão
        $payload = [
            'titulo' => 'Novo Endereço Padrão',
            'destinatario' => 'User',
            'telefone' => '51999998888',
            'cep' => '93000000',
            'logradouro' => 'Avenida Nova',
            'numero' => '456',
            'bairro' => 'Centro',
            'cidade' => 'São Martinho',
            'estado' => 'RS',
            'padrao' => true // Define como novo padrão
        ];

        $this->postJson('/api/addresses', $payload)->assertStatus(201);

        // 3. O antigo precisa ter sido resetado para false automaticamente pelo seu Service!
        $this->assertFalse($oldAddress->fresh()->padrao);
    }

    /**
     * Teste: show (Exibir endereço específico do próprio usuário)
     */
    public function test_should_show_specific_address_belonging_to_the_user(): void
    {
        Sanctum::actingAs($this->user);

        $address = Address::factory()->create([
            'user_id' => $this->user->id,
            'titulo' => 'Endereço Secreto'
        ]);

        $response = $this->getJson("/api/addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['titulo' => 'Endereço Secreto']);
    }

    /**
     * Teste de Segurança: show (Barrar se tentar ver endereço de outro usuário)
     */
    public function test_should_not_show_address_belonging_to_another_user(): void
    {
        Sanctum::actingAs($this->user);

        // Cria um endereço que pertence a OUTRA pessoa
        $stranger = User::factory()->create();
        $strangerAddress = Address::factory()->create([
            'user_id' => $stranger->id,
            'titulo' => 'Rua do Invasor'
        ]);

        $response = $this->getJson("/api/addresses/{$strangerAddress->id}");

        // O model retorna 404 (Not Found) por causa do firstOrFail combinando id + user_id
        $response->assertStatus(404);
    }

    /**
     * Teste: update (Atualizar com sucesso)
     */
    public function test_should_update_address_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $address = Address::factory()->create([
            'user_id' => $this->user->id,
            'titulo' => 'Nome Antigo'
        ]);

        $payload = [
            'titulo' => 'Nome Totalmente Novo',
            'destinatario' => 'Saymon Modificado',
            'telefone' => '51999998888',
            'cep' => '93000000',
            'logradouro' => 'Rua Alterada',
            'numero' => '99',
            'bairro' => 'Centro',
            'cidade' => 'São Martinho',
            'estado' => 'RS',
        ];

        $response = $this->putJson("/api/addresses/{$address->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['titulo' => 'Nome Totalmente Novo']);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'titulo' => 'Nome Totalmente Novo'
        ]);
    }

    /**
     * Teste: destroy (Deletar com sucesso)
     */
    public function test_should_delete_address_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $address = Address::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/addresses/{$address->id}");
        dump($response);
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Endereço excluído com sucesso.']);

        $this->assertSoftDeleted('addresses', [
            'id' => $address->id
        ]);
    }

    /**
     * Teste de Segurança: destroy (Barrar se tentar deletar endereço alheio)
     */
    public function test_should_not_allow_deleting_address_owned_by_another_user(): void
    {
        Sanctum::actingAs($this->user);

        $stranger = User::factory()->create();
        $strangerAddress = Address::factory()->create(['user_id' => $stranger->id]);

        $response = $this->deleteJson("/api/addresses/{$strangerAddress->id}");

        $response->assertStatus(404); // Protegido pelo firstOrFail no Service

        // Garante que o endereço do outro usuário continua intacto no banco
        $this->assertDatabaseHas('addresses', [
            'id' => $strangerAddress->id
        ]);
    }
}
