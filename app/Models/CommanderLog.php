<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CommanderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'commander_id',
        'target_id_type',
        'target_id',
        'operation',
        'param'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'commander_id', 'id');
    }
    
}
