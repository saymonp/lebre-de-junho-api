<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Product;

class ShippingController extends Controller
{
    public function calculate(Request $request)
    {
        $request->validate([
            'postal_code' => 'required|string|size:8',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        $postalCode = $request->input('postal_code');
        $cartItems = $request->input('items');

        // 1. Monta o array de produtos com as dimensões reais vindas do seu banco de dados
        $productsPayload = [];
        foreach ($cartItems as $item) {
            $product = Product::find($item['id']);

            $productsPayload[] = [
                'id' => (string) $product->id,
                'weight' => (float) ($product->weight ?? 0.3), // Peso padrão 300g se nulo
                'width' => (int) ($product->width ?? 11),      // Padrão mínimo Correios
                'height' => (int) ($product->height ?? 4),
                'length' => (int) ($product->length ?? 16),
                'quantity' => (int) $item['quantity'],
                'insurance_value' => (float) $product->price   // Seguro opcional baseado no valor
            ];
        }

        // 2. Dispara a requisição para os servidores do Melhor Envio
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env('MELHOR_ENVIO_TOKEN'),
            'User-Agent' => 'lebre de junho api' . env('MELHOR_ENVIO_EMAIL')
        ])->post(env('MELHOR_ENVIO_URL') . '/api/v2/me/shipment/calculate', [
            'from' => [
                'postal_code' => env('MELHOR_ENVIO_FROM_CEP')
            ],
            'to' => [
                'postal_code' => $postalCode
            ],
            'products' => $productsPayload
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Falha ao calcular o frete com o parceiro logístico.'
            ], 502);
        }

        // 3. Filtra e limpa o retorno (remove erros e ordena por preço mais barato)
        $options = collect($response->json())
            ->filter(fn($carrier) => !isset($carrier['error'])) // Remove agências indisponíveis
            ->map(fn($carrier) => [
                'id' => $carrier['id'],
                'name' => $carrier['name'],                     // Ex: "Jadlog"
                'company' => $carrier['company']['name'],       // Ex: "Jadlog Logística"
                'price' => (float) $carrier['price'],           // Preço final
                'delivery_time' => (int) $carrier['delivery_time'], // Prazo em dias úteis
                'custom_delivery_time' => (int) $carrier['custom_delivery_time'],
                'error' => $carrier['error'] ?? null
            ])
            ->sortBy('price') // O mais barato primeiro
            ->values();

        return response()->json($options);
    }
}
