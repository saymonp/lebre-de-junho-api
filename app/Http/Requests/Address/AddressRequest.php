<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo'       => 'required|string|max:50', // Ex: "Casa", "Trabalho"
            'destinatario' => 'required|string|max:255', // Quem vai receber

            // Mantemos como string para aceitar máscaras do frontend ou strings puras de números
            'telefone'     => 'required|string|max:20',
            'cep'          => 'required|string|max:9', // Aceita "99999-999" ou "99999999"
            'logradouro'   => 'required|string|max:255',
            'numero'       => 'required|string|max:20',
            'bairro'       => 'required|string|max:255',
            'cidade'       => 'required|string|max:255',
            'estado'       => 'required|string|size:2',
            'complemento'  => 'nullable|string|max:255',
            'padrao'       => 'nullable|boolean'
        ];
    }
}
