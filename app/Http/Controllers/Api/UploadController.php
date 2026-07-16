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

        // 1. Gera um nome de arquivo seguro usando UUID para evitar sobrescrever arquivos
        $extension = pathinfo($validated['filename'], PATHINFO_EXTENSION);
        $safeName = Str::uuid() . '.' . $extension;

        // Caminho final dentro do Bucket (Ex: products/gallery/66df65c3-8f0a...jpg)
        $path = "products/{$validated['folder']}/{$safeName}";

        // 2. Obtém o client nativo do SDK da AWS configurado no Laravel
        $disk = Storage::disk('s3');
        /** @disregard */
        $client = $disk->getClient();

        // 3. Monta o comando de PutObject injetando o Content-Type para assinatura rígida
        /** @disregard */
        $command = $client->getCommand('PutObject', [
            'Bucket' => $disk->getConfig()['bucket'],
            'Key' => $path,
            'ContentType' => $validated['content_type'],
        ]);

        // 4. Cria a requisição assinada temporária (válida por 20 minutos)
        $presignedRequest = $client->createPresignedRequest(
            $command,
            '+20 minutes'
        );

        // URL temporária usada pelo frontend para fazer o PUT do arquivo
        $uploadUrl = (string) $presignedRequest->getUri();

        // URL pública permanente que será persistida no banco do Laravel
        /** @disregard */
        $fileUrl = $disk->url($path);

        return response()->json([
            'upload_url' => $uploadUrl,
            'file_url' => $fileUrl,
        ]);
    }
}