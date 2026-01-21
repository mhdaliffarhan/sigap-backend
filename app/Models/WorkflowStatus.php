<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowStatus extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'label',
        'color',
        'is_end_state'
    ];

    protected $casts = [
        'is_end_state' => 'boolean',
    ];
}
