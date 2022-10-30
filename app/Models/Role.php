<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'ch_name',
        'name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_id', 'role_id');
    }
    public function permissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }
    
}
