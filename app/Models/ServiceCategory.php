<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'icon',
        'description',
        'form_schema',
        'is_active'
    ];

    // Casting otomatis: Database JSON -> Array PHP
    protected $casts = [
        'form_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }
}
