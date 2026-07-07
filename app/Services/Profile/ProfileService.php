<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ProfileService
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

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Verificamos se o usuário já existe antes do updateOrCreate
        $userExists = User::where('email', $googleUser->email)->exists();

        $user = User::updateOrCreate([
            'email' => $googleUser->email,
        ], [
            'name' => $googleUser->name,
            'google_id' => $googleUser->id,
            'avatar' => $googleUser->avatar,
        ]);

        // Se for um novo usuário, atribui a role padrão
        if (!$userExists) {
            $user->assignRole('user');
        }

        $apiToken = $user->createToken('auth_token')->plainTextToken;

        // Redireciona para o frontendlevando o token na URL
        // O frontend captura o token e guarda no localStorage
        $frontend = config('services.google.redirect');

        return redirect($frontend . "/auth/success?token={$apiToken}");
    }

    public function register(array $data): User
    {
        // Criptografa a senha com segurança
        $data['password'] = Hash::make($data['password']);

        // Cria o usuário no banco
        $user = User::create($data);

        // Todo usuário registrado pelo site começa com a role 'user'
        $user->assignRole('user');

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

    public function validateEmail() {}

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
            /**
             * TODO SendEmailJob
             */

            /**
            *SendEmailJob::dispatch(
            *    $user->email,
            *    'Recuperação de Senha - Lebre de Junho',
            *    "<h1>Olá, {$user->name}!</h1>
            * <p>Recebemos uma solicitação para redefinir a sua senha.</p>
            * <p>Clique no link abaixo para prosseguir:</p>
            * <a href='{$frontUrl}/reset-password?token={$token}&email=" . urlencode($user->email) . "'>Redefinir Senha</a>"
            *);
            */
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
}
