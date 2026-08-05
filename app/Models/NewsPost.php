<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsPost extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'image_path', 'published_at', 'is_published'];
    protected $casts = ['published_at' => 'datetime', 'is_published' => 'boolean'];
    public function getRouteKeyName(): string { return 'slug'; }
}
