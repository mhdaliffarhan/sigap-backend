<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowTransition extends Model
{
    use HasUuids;

    protected $fillable = [
        'service_category_id',
        'from_status_id',
        'to_status_id',
        'action_label',
        'trigger_role',
        'target_assignee_role',
        'required_form_schema'
    ];

    protected $casts = [
        'required_form_schema' => 'array',
    ];
}
