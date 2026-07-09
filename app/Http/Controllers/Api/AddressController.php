<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Address\AddressService;
use App\Http\Requests\AssignPermissionsRequest;
use App\Http\Requests\Address\AddressRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddressController extends Controller
{
    protected AddressService $addressService;

    public function __construct(AddressService $service)
    {
        $this->addressService = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $user_id = $request->user()->id;

        $addresses = $this->addressService->getAddresses($user_id);

        return response()->json($addresses, 200);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $data = [
            'user_id' => $request->user()->id,
            'id'      => $id
        ];

        $address = $this->addressService->getAddress($data);

        return response()->json($address, 200);
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'user_id' => $request->user()->id
        ]);

        $address = $this->addressService->create($data);

        return response()->json($address, 201);
    }

    public function update(AddressRequest $request, int $id): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'user_id' => $request->user()->id,
            'id' => $id
        ]);

        $address = $this->addressService->update($data);

        return response()->json($address, 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $data = [
            'user_id' => $request->user()->id,
            'id'      => $id
        ];

        $this->addressService->delete($data);

        return response()->json([
            'message' => 'Endereço excluído com sucesso.'
        ], 200);
    }
}
