<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'titulo' => $this->titulo,
            'destinatario' => $this->destinatario,
            'telefone' => $this->telefone,
            'cep' => $this->cep,
            'padrao' => $this->padrao,
            // Linha única formatada para facilitar a exibição na Dashboard/Nuxt
            'endereco_completo' => "{$this->logradouro}, nº {$this->numero} - {$this->bairro}, {$this->cidade}/{$this->estado}",
            'detalhes' => [
                'logradouro' => $this->logradouro,
                'numero' => $this->numero,
                'bairro' => $this->bairro,
                'cidade' => $this->cidade,
                'estado' => $this->estado,
                'complemento' => $this->complemento,
            ]
        ];
    }
}
