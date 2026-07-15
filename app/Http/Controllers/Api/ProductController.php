<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\Product\ProductService;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $service)
    {
        $this->productService = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = $this->productService->list();

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();

        $product = $this->productService->create($data);

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        // Carrega tudo de forma eficiente em uma única consulta otimizada
        $product = Product::with(['categories', 'materials', 'photos'])->findOrFail($id);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, int $id)
    {
        // Obtém os dados validados pelo ProductRequest
        $data = $request->validated();

        $data['id'] = $id;

        $product = $this->productService->update($data);

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $data = [
            'id' => $id
        ];

        $this->productService->delete($data);

        return response()->json([
            'message' => 'Produto excluído com sucesso.'
        ], 200);
    }
}
