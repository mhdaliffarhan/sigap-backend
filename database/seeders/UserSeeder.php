<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@bps.go.id',
                'password' => 'BpsNTB!2026',
                'roles' => ['super_admin'],
                'nip' => '199001011990101001',
                'jabatan' => 'Kepala Sistem IT',
                'unit_kerja' => 'IT & Sistem',
                'phone' => '081234567890',
                'is_active' => true,
            ],
            [
                'name' => 'Admin Layanan',
                'email' => 'admin.layanan@bps.go.id',
                'password' => 'BpsNTB!2026',
                'roles' => ['admin_layanan'],
                'nip' => '199102021991021001',
                'jabatan' => 'Admin Layanan',
                'unit_kerja' => 'Bagian Layanan',
                'phone' => '081234567891',
                'is_active' => true,
            ],
            [
                'name' => 'Admin Penyedia',
                'email' => 'admin.penyedia@bps.go.id',
                'password' => 'BpsNTB!2026',
                'roles' => ['admin_penyedia'],
                'nip' => '199103031991031001',
                'jabatan' => 'Admin Pengadaan',
                'unit_kerja' => 'Bagian Pengadaan',
                'phone' => '081234567892',
                'is_active' => true,
            ],
            [
                'name' => 'Teknisi Teknis',
                'email' => 'teknisi@bps.go.id',
                'password' => 'BpsNTB!2026',
                'roles' => ['teknisi'],
                'nip' => '199104041991041001',
                'jabatan' => 'Admin Teknis',
                'unit_kerja' => 'IT & Sistem',
                'phone' => '081234567893',
                'is_active' => true,
            ],
            [
                'name' => 'Pegawai Biasa',
                'email' => 'pegawai@bps.go.id',
                'password' => 'BpsNTB!2026',
                'roles' => ['pegawai'],
                'nip' => '199105051991051001',
                'jabatan' => 'Pegawai BPS',
                'unit_kerja' => 'Statistik Produksi',
                'phone' => '081234567894',
                'is_active' => true,
            ],
        ];

        // Standardized role priority
        $rolePriority = ['super_admin', 'admin_layanan', 'admin_penyedia', 'teknisi', 'admin_ga', 'admin_kendaraan', 'admin_ruangan', 'admin_zoom', 'pegawai'];

        foreach ($users as $userData) {
            $username = explode('@', $userData['email'])[0];
            $primaryRole = 'pegawai'; 

            foreach ($rolePriority as $role) {
                if (in_array($role, $userData['roles'])) {
                    $primaryRole = $role;
                    break;
                }
            }

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $username,
                    'password' => Hash::make($userData['password']),
                    'roles' => $userData['roles'],
                    'role' => $primaryRole,
                    'nip' => $userData['nip'],
                    'jabatan' => $userData['jabatan'],
                    'unit_kerja' => $userData['unit_kerja'],
                    'phone' => $userData['phone'],
                    'is_active' => $userData['is_active'],
                ]
            );
        }
    }
}
