<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
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

}
