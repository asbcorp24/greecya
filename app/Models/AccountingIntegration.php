<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','driver','endpoint_url','username','password','token','organization_code',
        'format_version','options','is_active','last_synced_at',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'token' => 'encrypted',
        'options' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function runs(){ return $this->hasMany(AccountingSyncRun::class); }
}
