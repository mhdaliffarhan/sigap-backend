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
        'type',
        'handling_role',
        'icon',
        'description',
        'form_schema',       // Schema Input User
        'action_schema',     // Schema Output PJ (Laporan)
        'is_active',
        'is_resource_based',  // Apakah butuh kalender?
        'target_role',
        'assignment_type',
        'default_assignee_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_resource_based' => 'boolean',
        'form_schema' => 'array',
        'action_schema' => 'array',
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

    // Relasi ke User default (untuk assignment direct)
    public function defaultAssignee()
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    // Relasi ke Tiket
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
