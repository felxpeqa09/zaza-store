<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeepalivePing extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source',
        'pinged_at',
    ];

    protected function casts(): array
    {
        return [
            'pinged_at' => 'datetime',
        ];
    }
}
