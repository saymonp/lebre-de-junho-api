<?php

namespace Tests\Feature\Api\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends TestCase
{
    // A Trait que limpa o banco a cada teste no PHPUnit é declarada aqui dentro
    use RefreshDatabase;

    /**
     * Teste: Registro com sucesso e disparo de Job.
     */
    public function test_should_register_a_user_successfully_and_dispatch_email_job(): void
    {
        // Roda os Seeders populando as roles no banco temporário do teste
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // 1. Intercepta a fila
        Bus::fake();

        // 2. Dados da requisição
        $payload = [
            'name' => 'Saymon Teste',
            'email' => 'saymon@lebredejunho.com',
            'password' => 'senha123',
            'aceitou_termos' => true,
        ];

        // 3. Executa a requisição (No PHPUnit usamos $this->...)
        $response = $this->postJson('/api/register', $payload);

        // 4. Asserts (Validações)
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email']
            ])
            ->assertJsonFragment([
                'email' => 'saymon@lebredejunho.com'
            ]);

        // Valida banco de dados
        $this->assertDatabaseHas('users', [
            'email' => 'saymon@lebredejunho.com',
            'email_verified_at' => null
        ]);

        // Valida se o Job entrou na fila
        Bus::assertDispatched(SendEmailJob::class);
    }
    /**
     * Teste: Registro com sucesso e validação do token hasheado no banco.
     */
    public function test_should_store_hashed_verification_token_in_database_upon_registration(): void
    {
        // 1. Intercepta a fila para capturar o Job de e-mail enviado
        Bus::fake();

        $email = 'token_test@lebredejunho.com';
        $payload = [
            'name' => 'Teste Token Banco',
            'email' => $email,
            'password' => 'senha123',
            'aceitou_termos' => true,
        ];

        // 2. Dispara o registro
        $this->postJson('/api/register', $payload)->assertStatus(201);

        // 3. Garante que o registro do token existe na tabela auxiliar
        $this->assertDatabaseHas('email_verification_tokens', [
            'email' => $email
        ]);

        // 4. Recupera o token criptografado direto do banco de dados
        $tokenNoBanco = DB::table('email_verification_tokens')
            ->where('email', $email)
            ->value('token');

        // 5. Inspeciona a fila de Jobs e captura o token limpo enviado no e-mail
        Bus::assertDispatched(SendEmailJob::class, function (SendEmailJob $job) use ($tokenNoBanco) {
            // Usamos uma expressão regular (Regex) simples para achar o ?token=... dentro da string de HTML do e-mail
            preg_match('/token=([a-zA-Z0-9]+)/', $job->htmlContent, $matches);

            $tokenLimpoDoEmail = $matches[1] ?? null;

            // Se encontrou o token no e-mail, valida se ele bate com a hash salva no banco
            return $tokenLimpoDoEmail && Hash::check($tokenLimpoDoEmail, $tokenNoBanco);
        });
    }

    /**
     * Teste: Falha no registro por não aceitar os termos.
     */
    public function test_should_not_register_a_user_if_terms_are_not_accepted(): void
    {
        $payload = [
            'name' => 'Saymon Teste',
            'email' => 'saymon@lebredejunho.com',
            'password' => 'senha123',
            'aceitou_termos' => false, // Inválido
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['aceitou_termos']);
    }

    /**
     * Teste: Sucesso na verificação de e-mail.
     */
    public function test_should_verify_email_successfully_with_valid_token(): void
    {
        // 1. Cria usuário fake
        $user = User::factory()->create([
            'email' => 'verificar@lebredejunho.com',
            'email_verified_at' => null
        ]);

        // 2. Insere token no banco
        $tokenLimpo = 'token_secreto_de_teste_60_caracteres';
        DB::table('email_verification_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($tokenLimpo),
            'created_at' => now(),
        ]);

        $payload = [
            'email' => $user->email,
            'token' => $tokenLimpo,
        ];

        $response = $this->postJson('/api/verify/email', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user'
            ]);

        // Força o Laravel a recarregar o modelo do banco e checa se foi verificado
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Garante que o token foi limpo do banco
        $this->assertDatabaseMissing('email_verification_tokens', [
            'email' => $user->email
        ]);
    }

    /**
     * Teste: Falha na verificação com token incorreto.
     */
    public function test_should_not_verify_email_if_token_is_invalid(): void
    {
        $user = User::factory()->create([
            'email' => 'verificar@lebredejunho.com',
            'email_verified_at' => null
        ]);

        DB::table('email_verification_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('token_correto'),
            'created_at' => now(),
        ]);

        $payload = [
            'email' => $user->email,
            'token' => 'token_completamente_errado',
        ];

        $response = $this->postJson('/api/verify/email', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
