<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

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
        //$this->admin = User::factory()->create();
        //$this->admin->assignRole('admin');
    }

    public function test_deve_autenticar_e_redirecionar_para_o_frontend_com_o_token(): void
    {
        config(['services.google.redirect_frontend' => 'https://lebredejunho.shop']);

        // 2. Instancia o objeto REAL de usuário do Socialite em vez de um mock genérico
        $googleUser = new SocialiteUser();
        $googleUser->map([
            'id'       => 'google-id-123456',
            'name'     => 'João Silva',
            'email'    => 'joao@gmail.com',
            'avatar'   => null,
        ]);
        $googleUser->token = 'fake-google-token';

        // 3. Mocka apenas o Provider do Socialite
        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // 4. Executa a requisição na rota do callback
        $response = $this->get('/auth/google/callback');

        // 5. Assertions
        $this->assertDatabaseHas('users', [
            'email' => 'joao@gmail.com',
            'name'  => 'João Silva',
        ]);

        $user = User::where('email', 'joao@gmail.com')->first();

        // Garante que o token do Sanctum foi gerado
        $this->assertCount(1, $user->tokens);

        // Valida que o redirecionamento aponta para a URL de sucesso com a query token
        $response->assertStatus(302);
        $response->assertRedirectContains('https://lebredejunho.shop/auth/success?token=');
    }

    public function test_deve_redirecionar_para_tela_de_erro_quando_o_google_falhar(): void
    {
        config(['services.google.redirect_frontend' => 'https://lebredejunho.shop']);

        // Simula uma exceção lançada pelo Socialite (ex: token inválido)
        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth Error'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // Executa a chamada
        $response = $this->get('/auth/google/callback');

        // Assertions
        $response->assertStatus(302);
        $response->assertRedirect('https://lebredejunho.shop/auth/error?message=failed_google_auth');
        $this->assertDatabaseCount('users', 0);
    }
}