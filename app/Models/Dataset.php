<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'demo_data',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'demo_data' => 'array',
        ];
    }
}
