<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['customer_id','trainer_id','name','email','phone','role','password'];
    protected $hidden = ['password','remember_token'];
    protected $casts = ['email_verified_at'=>'datetime'];

    public function customer(){ return $this->belongsTo(Customer::class); }
    public function trainer(){ return $this->belongsTo(Trainer::class); }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')->withPivot('allowed')->withTimestamps();
    }

    public function roleLabel(): string
    {
        return config('access.roles.'.$this->role, ucfirst((string) $this->role));
    }

    public function hasPermission(string $code): bool
    {
        if ($this->role === 'customer') return false;

        $defaults = config('access.defaults.'.$this->role, []);
        $fallback = in_array('*', $defaults, true) || in_array($code, $defaults, true);

        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permissions') || !Schema::hasTable('user_permissions')) return $fallback;

        $permissionId = DB::table('permissions')->where('code',$code)->value('id');
        if (!$permissionId) return $fallback;

        $override = DB::table('user_permissions')->where('user_id',$this->id)->where('permission_id',$permissionId)->first();
        if ($override) return (bool)$override->allowed;

        $hasRoleRows = DB::table('role_permissions')->where('role',$this->role)->exists();
        if (!$hasRoleRows) return $fallback;

        return DB::table('role_permissions')->where('role',$this->role)->where('permission_id',$permissionId)->exists();
    }

    public function canSeePersonalData(): bool
    {
        return $this->hasPermission('customers.personal_data');
    }
}
