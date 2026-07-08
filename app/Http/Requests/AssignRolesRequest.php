<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Proteção por middleware de admin na rota
    }

    public function rules(): array
    {
        return [
            'roles'   => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ];
    }
}