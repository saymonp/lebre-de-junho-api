<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            
            // Mídias
            'cover_photo_path' => $this->cover_photo_path,
            'diagram' => $this->diagram,

            // Preços (Garante o retorno como float/double em vez de string)
            'price' => (float) $this->price,
            'promotional_price' => $this->promotional_price ? (float) $this->promotional_price : null,
            'discount_pix' => (int) $this->discount_pix,
            
            // Dimensões e Peso
            'weight' => (float) $this->weight,
            'height' => (float) $this->height,
            'width' => (float) $this->width,
            'length' => (float) $this->length,

            // Controle e Produção
            'stock' => (int) $this->stock,
            'in_stock' => $this->stock > 0, // Facilita a lógica de exibição no frontend
            'days_to_create' => (int) $this->days_to_create,

            // Relacionamentos carregados dinamicamente
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->pluck('name'); // Retorna apenas array de strings ['Categoria A', 'Categoria B']
            }),
            
            'materials' => $this->whenLoaded('materials', function () {
                return $this->materials->pluck('name'); // Retorna apenas array de strings ['Fio de Algodão', 'Madeira']
            }),

            'photos' => $this->whenLoaded('photos', function () {
                return $this->photos->pluck('photo_url'); // Retorna apenas array de URLs das fotos adicionais
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}