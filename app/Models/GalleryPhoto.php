<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryPhoto extends Model
{
    use HasFactory;
    protected $fillable = ['gallery_album_id', 'image_path', 'title', 'caption', 'sort_order', 'is_published'];
    protected $casts = ['is_published' => 'boolean'];
    public function album() { return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id'); }
}
