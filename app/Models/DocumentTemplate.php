<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DocumentTemplate extends Model { use HasFactory; protected $fillable=['name','type','body','is_active']; protected $casts=['is_active'=>'boolean']; }
