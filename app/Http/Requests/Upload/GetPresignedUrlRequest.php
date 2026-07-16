<?php

namespace App\Http\Requests\Upload;

use Illuminate\Foundation\Http\FormRequest;

class GetPresignedUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filename' => 'required|string',
            'content_type' => 'required|string', // Ex: image/jpeg, application/pdf
            'folder' => 'required|string|in:gallery,covers,diagrams', // Pastas permitidas
        ];
    }
}