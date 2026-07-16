<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\GetPresignedUrlRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Gera uma URL assinada (Presigned URL) para upload direto para o MinIO/S3.
     */
    public function generatePresignedUrl(GetPresignedUrlRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $extension = pathinfo($validated['filename'], PATHINFO_EXTENSION);
        $safeName = Str::uuid() . '.' . $extension;

        // Caminho final dentro do Bucket (Ex: products/gallery/66df65c3-8f0a...jpg)
        $path = "products/{$validated['folder']}/{$safeName}";

        $disk = Storage::disk('s3');

        // 1. Gera a URL temporária para o frontend fazer o PUT do arquivo (expira em 20 minutos)
        /** @disregard */
        $uploadUrl = $disk->temporaryUrl(
            $path,
            now()->addMinutes(20),
            [
                'ContentType' => $validated['content_type'],
            ]
        );

        // 2. Monta a URL pública permanente do arquivo
        /** @disregard */
        $fileUrl = $disk->url($path);

        return response()->json([
            'upload_url' => $uploadUrl,
            'file_url' => $fileUrl,
        ]);
    }
}
