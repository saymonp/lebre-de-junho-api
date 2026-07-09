<?php

namespace App\Services\Address;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Jobs\SendEmailJob;

class AddressService
{
    public function __construct() {}

    public function getAddress(array $data): Address
    {
        return Address::where('id', $data['id'])
            ->where('user_id', $data['user_id'])
            ->firstOrFail();
    }

    public function getAddresses(int $user_id)
    {
        return Address::where('user_id', $user_id)
        ->orderBy('padrao', 'desc')
        ->paginate(5)->withQueryString();
    }

    public function create(array $data): Address
    {
        // Se o novo endereço está sendo salvo como padrão...
        if (!empty($data['padrao']) && $data['padrao'] === true) {
            $this->resetarOutrosEnderecosPadrao($data['user_id']);
        }

        return Address::create($data);
    }

    public function update(array $data): Address
    {
        // Garante que o usuário só altere o seu endereço
        $address = Address::where('id', $data['id'])
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        // Se o endereço foi atualizado para padrão...
        if (!empty($data['padrao']) && $data['padrao'] === true) {
            $this->resetarOutrosEnderecosPadrao($data['user_id']);
        }

        $address->update($data);

        return $address;
    }

    public function delete(array $data): bool
    {
        // Garante que o usuário só altere o seu endereço
        $address = Address::where('id', $data['id'])
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $address->delete();

        return true;
    }

    /**
     * Remove o status de padrão de todos os endereços do usuário
     */
    private function resetarOutrosEnderecosPadrao(int $userId): void
    {
        // Executa um UPDATE direto no banco em lote, rápido
        Address::where('user_id', $userId)
            ->where('padrao', true)
            ->update(['padrao' => false]);
    }
}
