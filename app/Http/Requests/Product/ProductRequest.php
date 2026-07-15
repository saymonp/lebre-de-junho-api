<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            // Informações Básicas
            'name' => 'required|string|max:255',
            'description' => 'required|string',

            // Preços e Descontos
            'price' => 'required|numeric|min:0|decimal:0,2',
            'promotional_price' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
                'lt:price', // O preço promocional deve ser menor que o preço original (Less Than)
            ],
            'discount_pix' => 'nullable|integer|between:0,100', // Desconto em porcentagem de 0 a 100%

            // Dimensões e Peso (essenciais para cálculo de frete)
            'weight' => 'required|numeric|min:0.001|decimal:0,3', // Mínimo de 1 grama
            'height' => 'required|numeric|min:0|decimal:0,2',
            'width' => 'required|numeric|min:0|decimal:0,2',
            'length' => 'required|numeric|min:0|decimal:0,2',

            // Diagrama do produto (string/caminho do arquivo)
            'diagram' => 'nullable|string|max:255',

            // Controle e Produção
            'stock' => 'required|integer|min:0',
            'days_to_create' => 'required|integer|min:0',

            // Relacionamentos (Associações de Categorias e Materiais)
            'category_ids' => 'required|array|min:1', // Exige pelo menos uma categoria vinculada
            'category_ids.*' => 'integer|exists:categories,id', // Valida se cada ID existe na tabela de categorias

            'material_ids' => 'nullable|array', // Materiais podem ser opcionais
            'material_ids.*' => 'integer|exists:materials,id', // Valida se cada ID existe na tabela de materiais
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome do produto',
            'description' => 'descrição',
            'price' => 'preço',
            'promotional_price' => 'preço promocional',
            'discount_pix' => 'desconto do PIX',
            'weight' => 'peso',
            'height' => 'altura',
            'width' => 'largura',
            'length' => 'comprimento',
            'diagram' => 'diagrama do produto',
            'stock' => 'estoque',
            'days_to_create' => 'dias para produção',
            'category_ids' => 'categorias',
            'category_ids.*' => 'categoria selecionada',
            'material_ids' => 'materiais',
            'material_ids.*' => 'material selecionado',
        ];
    }
}
