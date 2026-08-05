<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'subtitle', 'image_path', 'button_text', 'button_url', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
