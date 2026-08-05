<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryAlbum extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'slug', 'description', 'cover_path', 'is_published', 'sort_order'];
    protected $casts = ['is_published' => 'boolean'];
    public function getRouteKeyName(): string { return 'slug'; }
    public function photos() { return $this->hasMany(GalleryPhoto::class)->orderBy('sort_order')->orderBy('id'); }
}
