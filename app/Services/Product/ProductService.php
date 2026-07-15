<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Jobs\SendEmailJob;

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
        $product = Product::create($data);

        return $product;
    }

    public function update(array $data): Product
    {
        $product = Product::where('id', $data['id'])
            ->firstOrFail();

        $product->update($data);

        return $product;
    }

    public function delete(array $data): bool
    {
        $product = Product::where('id', $data['id'])
            ->firstOrFail();

        $product->delete();

        return true;
    }

    public function list(array $data)
    {
        return Product::paginate(10)->withQueryString();
    }
}
