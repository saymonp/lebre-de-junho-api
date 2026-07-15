<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

}
