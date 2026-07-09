<?php

namespace Tests\Feature\Api\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    // Limpa o banco a cada teste
    use RefreshDatabase;

    /**
     * Teste: Login com sucesso.
     */
    public function test_should_login_a_user_successfully(): void
    {
        $password = 'user123';
        $email = 'user@email.com';

        User::factory()->create(['email' => $email, 'password' => $password]);

        $response = $this->postJson('/api/login', ['email' => $email, 'password' => $password]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'user' => ['id', 'name', 'email']
            ])
            ->assertJsonFragment([
                'email' => 'user@email.com'
            ]);
    }

    /**
     * Teste: Login falha com credenciais inválidas.
     */
    public function test_should_not_authenticate_user_with_invalid_password(): void
    {
        $password = 'user123';
        $email = 'user@email.com';

        User::factory()->create(['email' => $email, 'password' => $password]);

        $response = $this->postJson('/api/login', ['email' => $email, 'password' => 'wrongPass']);
        dump($response);
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'As credenciais fornecidas estão incorretas.',
            ]);
    }
}
