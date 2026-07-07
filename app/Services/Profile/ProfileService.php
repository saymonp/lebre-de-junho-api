<?php

namespace App\Services\Profile;

use App\Aspects\ExecutionAspect;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct() {}

    public function getProfile(User $user)
    {
        // Limpo, direto e sem rodeios teatrais
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
}
