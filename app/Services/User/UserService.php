<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Jobs\SendEmailJob;

class UserService
{
    public function __construct() {}

    public function getProfile(User $user)
    {
        return $user;
    }

    public function updateProfile(User $user, array $data)
    {

        if (isset($data['new_password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                throw new \Exception('The current password is incorrect.');
            }

            $data['password'] = Hash::make($data['new_password']);
        }

        unset($data['current_password'], $data['new_password']);

        $user->update($data);

        return true;
    }

    /**
     * Resolve o cadastro/login via Google e retorna o Token de acesso.
     */
    public function loginOrCreateFromGoogle(SocialiteUser $googleUser): string
    {
        // 1. Verifica se o e-mail já existe para não sobrescrever papéis (roles) à toa
        $userExists = User::where('email', $googleUser->getEmail())->exists();

        // 2. Cria ou atualiza o vínculo do usuário
        $user = User::updateOrCreate([
            'email' => $googleUser->getEmail(),
        ], [
            'name'      => $googleUser->getName(),
            'google_id' => $googleUser->getId(),
            'email_verified_at' => now()
        ]);

        // 3. Se for uma conta nova, atribui a role de cliente comum
        if (!$userExists) {
            $user->assignRole('user');
        }

        // 4. Gera o token do Sanctum para esse usuário logado
        return $user->createToken('auth_token')->plainTextToken;
    }

    public function register(array $data): User
    {
        // Criptografa a senha com segurança
        $data['password'] = Hash::make($data['password']);

        // Cria o usuário no banco
        $user = User::create($data);

        // Todo usuário registrado pelo site começa com a role 'user'
        $user->assignRole('user');

        // --- Fluxo de Verificação de E-mail ---
        $token = Str::random(60);

        // Salva o token temporário associado ao e-mail
        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        $frontUrl = config('app.frontend_url');

        // Dispara o Job de e-mail em segundo plano
        SendEmailJob::dispatch(
            $user->email,
            'Confirme seu e-mail - Lebre de Junho',
            "<h1>Bem-vindo(a), {$user->name}!</h1>
             <p>Obrigado por se cadastrar na Lebre de Junho. Para ativar sua conta, confirme seu e-mail clicando no link abaixo:</p>
             <a href='{$frontUrl}/verify-email?token={$token}&email=" . urlencode($user->email) . "'>Confirmar E-mail</a>"
        );

        return $user;
    }

    /**
     * Valida o token e confirma o e-mail do usuário
     */
    public function verifyEmail(string $tokenUrl, string $email): User
    {
        $verifyRecord = DB::table('email_verification_tokens')
            ->where('email', $email)
            ->first();

        // 1. Verifica se o pedido de verificação existe e se não expirou (ex: 24 horas = 1440 minutos)
        if (! $verifyRecord || now()->parse($verifyRecord->created_at)->addMinutes(1440)->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['O link de verificação é inválido ou já expirou.'],
            ]);
        }

        // 2. Compara as hashes de segurança do token
        if (! Hash::check($tokenUrl, $verifyRecord->token)) {
            throw ValidationException::withMessages([
                'token' => ['O token de verificação fornecido é inválido.'],
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Incapaz de verificar o e-mail para este usuário.'],
            ]);
        }

        // 3. Atualiza o status do usuário se ele já não estiver verificado
        if (is_null($user->email_verified_at)) {
            $user->update([
                'email_verified_at' => now()
            ]);
        }

        // 4. Limpa o token utilizado
        DB::table('email_verification_tokens')->where('email', $email)->delete();

        return $user;
    }

    public function login(array $data): User
    {
        $user = User::where('email', $data['email'])->first();

        // 1. Verificação: Conta sem senha (criada via Google/Socialite)
        if ($user && $user->password === null) {
            throw ValidationException::withMessages([
                'email' => ['Esta conta utiliza login via Google. Por favor, entre com sua conta Google ou recupere sua senha.'],
            ]);
        }

        // 2. Verificação: Credenciais incorretas tradicionais
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        return $user;
    }

    /**
     * Admin deleta um usuário e seus endereços em modo Soft Delete
     */
    public function destroy(User $user): bool
    {
        // 1. Deleta (via soft delete) todos os endereços do usuário primeiro
        $user->addresses()->delete();

        // 2. Revoga TODOS os tokens de acesso (desloga de todos os dispositivos)
        $user->tokens()->delete();

        // 3. Deleta (via soft delete) o usuário
        $user->delete();

        return true;
    }

    /**
     * O próprio usuário deleta sua conta
     */
    public function deleteOwnAccount(User $user): bool
    {
        // 1. Deleta em modo Soft Delete todos os endereços do usuário
        $user->addresses()->delete();

        // 2. Revoga TODOS os tokens de acesso (desloga de todos os dispositivos)
        $user->tokens()->delete();

        // 3. Aplica o Soft Delete no usuário
        $user->delete();

        return true;
    }

    /**
     * Solicitar Recuperar Senha
     */
    public function recoverPassword(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $token = Str::random(60);
            // Salva ou atualiza o token na tabela padrão do Laravel (evita duplicados)
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            $frontUrl = config('app.frontend_url');

            // Dispara o Job enviando o $token limpo na URL para o cliente clicar
             SendEmailJob::dispatch(
                 $user->email,
                 'Recuperação de Senha - Lebre de Junho',
                 "<h1>Olá, {$user->name}!</h1>
              <p>Recebemos uma solicitação para redefinir a sua senha.</p>
              <p>Clique no link abaixo para prosseguir:</p>
              <a href='{$frontUrl}/reset-password?token={$token}&email=" . urlencode($user->email) . "'>Redefinir Senha</a>"
             );
        }

        return true;
    }

    public function resetPassword(string $token_url, string $email, string $new_password): User
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $resetRecord || now()->parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['O token de recuperação é inválido ou já expirou.'],
            ]);
        }

        if (!Hash::check($token_url, $resetRecord->token)) {
            throw ValidationException::withMessages([
                'token' => ['O token de recuperação fornecido é inválido.'],
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Não foi possível encontrar um usuário correspondente a este e-mail.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($new_password)
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        // Deleta tokens antigos por segurança
        $user->tokens()->delete();

        return $user;
    }

    /**
     * Sincroniza permissões diretas de um usuário
     */
    public function syncPermissions(User $user, array $permissions): User
    {
        // Executa dentro de uma transação por segurança
        return DB::transaction(function () use ($user, $permissions) {

            // O método do Spatie limpa as antigas e grava as novas
            $user->syncPermissions($permissions);

            return $user;
        });
    }

    /**
     * Sincroniza roles de um usuário específico
     */
    public function syncRoles(User $user, array $roles): User
    {
        return DB::transaction(function () use ($user, $roles) {

            // O método syncRoles do Spatie remove os roles antigos e adiciona os novos
            $user->syncRoles($roles);

            return $user;
        });
    }

    /**
     * Lista todos os usuários paginados com seus papéis e permissões
     */
    public function listUsers(int $perPage = 15): LengthAwarePaginator
    {
        // Trazemos as relações pré-carregadas e paginamos de 15 em 15 (ou o valor que preferir)
        return User::with(['roles', 'permissions'])
            ->latest() // Traz os usuários mais recentes primeiro
            ->paginate($perPage);
    }
}
