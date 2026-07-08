<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'slug' => $this->slug,
            'is_admin' => $this->is_admin,

            // 1. Proteção de performance usando whenLoaded para os Endereços
            // O JSON só vai incluir os endereços se você der um ->with('addresses') no Controller
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),

            // 2. Proteção de performance para o Spatie
            // Só executa as queries de permissão se a relação de roles tiver sido carregada (Eager Loading)
            'roles' => $this->whenRelationLoaded('roles', function () {
                return $this->getRoleNames();
            }),

            'permissions' => $this->whenRelationLoaded('permissions', function () {
                return $this->getAllPermissions()->pluck('name');
            }),

            // Dados temporais úteis para auditoria na dashboard
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
