<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

$users = User::all();
$roles = Role::all()->pluck('id', 'code');

foreach ($users as $user) {
    if (isset($roles[$user->role])) {
        $user->role_id = $roles[$user->role];
        $user->save();
        echo "Updated User: {$user->name} to Role ID: {$user->role_id}\n";
    } else {
        echo "Skipping User: {$user->name} (Role code '{$user->role}' not found in roles table)\n";
    }
}

echo "Done syncing user roles.\n";
