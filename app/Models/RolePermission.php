<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{

    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_id', 'role_id');
    }
    
}
