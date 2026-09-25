<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Panorama extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'is_active',
        'is_homepage',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_homepage' => 'boolean',
    ];
}
