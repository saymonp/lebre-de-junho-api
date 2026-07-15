<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase; // Garante que o banco de dados de teste seja limpo a cada rodada

    private User $adminUser;
    private User $commonUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Alimenta as Roles e Permissions necessárias para os testes no guard correto (api)
        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        Role::create(['name' => 'user', 'guard_name' => 'api']);

        // Cria o usuário administrador e atrela a Role
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Cria o usuário comum
        $this->commonUser = User::factory()->create();
        $this->commonUser->assignRole('user');

    }

    /**
     * Teste 1: Autenticação e Autorização (Regra Geral de Segurança)
     */
    public function test_unauthenticated_or_non_admin_users_cannot_access_product_routes(): void
    {
        // 1. Tenta acessar sem estar logado (Espera 401 Unauthorized ou redirecionamento se não for API)
        $responseUnauthenticated = $this->getJson('/api/products');
        $responseUnauthenticated->assertStatus(401);

        // 2. Tenta acessar com usuário logado mas sem ser administrador (Espera 403 Forbidden)
        $responseForbidden = $this->actingAs($this->commonUser, 'sanctum')
            ->getJson('/api/products');

        $responseForbidden->assertStatus(403);
    }

    /**
     * Teste 2: Listagem de Produtos (INDEX)
     */
    public function test_admin_can_list_products(): void
    {
        // Cria 3 produtos no banco de teste
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200);

        // Se o seu service retorna paginação, você pode validar a estrutura do JSON
        // Se retornar uma lista simples, valide a quantidade de itens retornados:
        $response->assertJsonCount(3);
    }

    /**
     * Teste 3: Detalhes de um Produto Específico (SHOW)
     */
    public function test_admin_can_show_product_with_its_relations(): void
    {
        $product = Product::factory()->create();

        // Associa categorias e materiais fictícios
        $category = Category::factory()->create(['name' => 'Decoração']);
        $material = Material::factory()->create(['name' => 'Fio de Algodão']);

        $product->categories()->attach($category);
        $product->materials()->attach($material);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonPath('data.categories', ['Decoração'])
            ->assertJsonPath('data.materials', ['Fio de Algodão']);
    }

    /**
     * Teste 4: Criação de Produto (STORE)
     */
    public function test_admin_can_create_a_product_successfully(): void
    {
        // Criamos previamente categorias e materiais para o sync do request
        $category1 = Category::factory()->create(['name' => 'Vasos']);
        $material1 = Material::factory()->create(['name' => 'Cerâmica']);

        $payload = [
            'name' => 'Vaso de Flores Lindo',
            'cover_photo_path' => 'https://s3.amazonaws.com/lebre-de-junho/products/covers/img.jpg',
            'description' => 'Um vaso perfeito para decorar mesas de escritório.',
            'price' => 89.90,
            'promotional_price' => 79.90,
            'discount_pix' => 10,
            'weight' => 0.450,
            'height' => 15.00,
            'width' => 10.00,
            'length' => 10.00,
            'stock' => 5,
            'days_to_create' => 3,
            'category' => ['Vasos'],
            'material' => ['Cerâmica'],
            'photos_paths' => [
                'https://s3.amazonaws.com/lebre-de-junho/products/gallery/img1.jpg',
                'https://s3.amazonaws.com/lebre-de-junho/products/gallery/img2.jpg'
            ]
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/products', $payload);

        $response->assertStatus(201); // Created (ou 200 dependendo do padrão de retorno do Laravel)

        // Verifica se persistiu na tabela principal de produtos
        $this->assertDatabaseHas('products', [
            'name' => 'Vaso de Flores Lindo',
            'price' => 89.90,
            'stock' => 5
        ]);

        // Verifica se criou as relações de fotos associadas
        $this->assertDatabaseHas('product_photos', [
            'photo_url' => 'https://s3.amazonaws.com/lebre-de-junho/products/gallery/img1.jpg'
        ]);
    }

    /**
     * Teste 5: Atualização de Produto (UPDATE)
     */
    public function test_admin_can_update_a_product_successfully(): void
    {
        $product = Product::factory()->create([
            'name' => 'Nome Antigo',
            'price' => 100.00
        ]);

        $payload = [
            'name' => 'Nome Totalmente Atualizado',
            'description' => 'Nova descrição do produto.',
            'cover_photo_path' => 'https://s3.amazonaws.com/lebre-de-junho/products/covers/update',
            'price' => 120.00,
            'weight' => 0.500,
            'height' => 10.00,
            'width' => 10.00,
            'length' => 10.00,
            'stock' => 10,
            'days_to_create' => 5,
            'category' => ['Nova Categoria'], // Irá disparar o firstOrCreate
            'material' => ['Novo Material']
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/products/{$product->id}", $payload);

        $response->assertStatus(200);

        // Garante que o banco de dados foi atualizado
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nome Totalmente Atualizado',
            'price' => 120.00
        ]);

        $this->assertDatabaseMissing('products', [
            'name' => 'Nome Antigo'
        ]);
    }

    /**
     * Teste 6: Exclusão de Produto (DESTROY)
     */
    public function test_admin_can_delete_a_product_successfully(): void
    {
        $product = Product::factory()->create();
        // Associa categorias e materiais fictícios
        $category = Category::factory()->create(['name' => 'Decoração']);
        $material = Material::factory()->create(['name' => 'Fio de Algodão']);

        $product->categories()->attach($category);
        $product->materials()->attach($material);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Produto excluído com sucesso.'
            ]);

        // Certifica que o produto não consta mais na tabela
        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }
}
