<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cover_photo_path',
        'price',
        'promotional_price',
        'discount_pix',
        'weight',
        'height',
        'width',
        'length',
        'diagram',
        'stock',
        'days_to_create'
    ];

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'material_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class, 'product_id');
    }

    public function syncRelations($relation, $modelClass, $names)
    {
        if (empty($names)) return;

        $ids = collect($names)->map(function ($name) use ($modelClass) {
            $record = $modelClass::firstOrCreate(['name' => trim($name)]);
            return $record->id;
        });

        $this->$relation()->sync($ids);
    }

    protected static function booted()
    {
        static::deleting(function ($product) {
            // Deleta a foto de capa se ela existir
            if ($product->cover_photo_path) {
                $path = parse_url($product->cover_photo_path, PHP_URL_PATH);
                // Remove o nome do bucket do início do path se necessário:
                $cleanPath = ltrim(str_replace('/lebre-de-junho/', '', $path), '/');
                Storage::disk('s3')->delete($cleanPath);
            }

            // Deleta a galeria de fotos
            if ($product->photos_paths) {
                foreach ($product->photos_paths as $photoUrl) {
                    $path = parse_url($photoUrl, PHP_URL_PATH);
                    $cleanPath = ltrim(str_replace('/lebre-de-junho/', '', $path), '/');
                    Storage::disk('s3')->delete($cleanPath);
                }
            }
        });
    }
}
