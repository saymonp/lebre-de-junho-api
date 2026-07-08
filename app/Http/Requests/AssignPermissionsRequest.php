<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A proteção de quem pode acessar (ex: apenas admin) fica no middleware da rota
        return true; 
    }

    public function rules(): array
    {
        return [
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }
}