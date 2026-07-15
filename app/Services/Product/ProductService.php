<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\Material;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct() {}

    public function getProduct(array $data): Product
    {
        return Product::where('id', $data['id'])
            ->firstOrFail();
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);

            if (filled($data['material'])) {
                $product->syncRelations('material', Material::class, $data['material'] ?? []);
            }
            if (filled($data['category'])) {
                $product->syncRelations('category', Category::class, $data['category'] ?? []);
            }
            if (filled($data['photos_paths'])) {
                // Transforma o array de strings em um array associativo aceito pelo createMany
                $photos = collect($data['photos_paths'])->map(fn($url) => ['photo_url' => $url])->toArray();
                $product->photos()->createMany($photos);
            }

            return $product;
        });
    }

    public function update(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::where('id', $data['id'])
                ->firstOrFail();

            $product->update($data);

            if (filled($data['material'])) {
                $product->syncRelations('materials', Material::class, $data['material'] ?? []);
            }
            if (filled($data['category'])) {
                $product->syncRelations('categories', Category::class, $data['category'] ?? []);
            }
            if (isset($data['photos_paths'])) {
                // Remove as fotos antigas da galeria para salvar o novo conjunto enviado
                $product->photos()->delete();

                $photos = collect($data['photos_paths'])->map(fn($url) => ['photo_url' => $url])->toArray();
                $product->photos()->createMany($photos);
            }

            return $product;
        });
    }

    public function delete(array $data): bool
    {
        $product = Product::where('id', $data['id'])
            ->firstOrFail();

        $product->delete();

        return true;
    }

    public function list(int $perPage = 10)
    {
        return Product::latest()->paginate($perPage)->withQueryString();
    }
}
