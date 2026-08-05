<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'label', 'type', 'value', 'options', 'is_public', 'sort_order'];

    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
    ];
}
