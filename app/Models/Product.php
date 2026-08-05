<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'type', 'description', 'price', 'visits_count', 'validity_days', 'is_active', 'sort_order'];
    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];
    public function certificates() { return $this->hasMany(Certificate::class); }
}
