<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceCategory extends Model
{
    use HasFactory;

    // Asumsi ID menggunakan UUID (sesuai migrasi awal)
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',        // booking, repair, service
        'icon',
        'is_active',

        // --- FIELD BARU ---
        'handling_role',     // Role default PJ (misal: 'teknisi', 'supir')
        'form_schema',       // Schema Input User
        'action_schema',     // Schema Output PJ (Laporan)
        'is_resource_based'  // Apakah butuh kalender?
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_resource_based' => 'boolean',
        'form_schema' => 'array',   // Auto convert JSON <-> Array
        'action_schema' => 'array', // Auto convert JSON <-> Array
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // Relasi ke Resource (Mobil/Ruangan)
    public function resources()
    {
        return $this->hasMany(Resource::class);
    }
}
