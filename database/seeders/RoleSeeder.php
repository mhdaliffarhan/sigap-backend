<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'super_admin', 'name' => 'Super Administrator'],
            ['code' => 'admin_layanan', 'name' => 'Admin Layanan (Helpdesk)'],
            ['code' => 'admin_penyedia', 'name' => 'Admin Penyedia (Vendor)'],
            ['code' => 'teknisi', 'name' => 'Teknisi IT'],
            ['code' => 'pegawai', 'name' => 'Pegawai User'],
            // ROLE BARU ANDA:
            ['code' => 'admin_ga', 'name' => 'Admin General Affair (GA)'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                [
                    'id' => Str::uuid(),
                    'name' => $role['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
