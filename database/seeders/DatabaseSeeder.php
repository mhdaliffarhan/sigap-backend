<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => 'password',
                'roles' => ['super_admin'],
                'nip' => '199001011990101001',
                'jabatan' => 'Kepala Sistem IT',
                'unit_kerja' => 'IT & Sistem',
                'phone' => '081234567890',
                'is_active' => true,
            ],
            [
                'name' => 'Admin Layanan',
                'email' => 'admin.layanan@example.com',
                'password' => 'password',
                'roles' => ['admin_layanan'],
                'nip' => '199102021991021001',
                'jabatan' => 'Admin Layanan',
                'unit_kerja' => 'Bagian Layanan',
                'phone' => '081234567891',
                'is_active' => true,
            ],
            [
                'name' => 'Admin Penyedia',
                'email' => 'admin.penyedia@example.com',
                'password' => 'password',
                'roles' => ['admin_penyedia'],
                'nip' => '199103031991031001',
                'jabatan' => 'Admin Penyedia',
                'unit_kerja' => 'Bagian Penyedia',
                'phone' => '081234567892',
                'is_active' => true,
            ],
            [
                'name' => 'Teknisi',
                'email' => 'teknisi@example.com',
                'password' => 'password',
                'roles' => ['teknisi'],
                'nip' => '199104041991041001',
                'jabatan' => 'Teknisi Maintenance',
                'unit_kerja' => 'IT & Sistem',
                'phone' => '081234567893',
                'is_active' => true,
            ],
            [
                'name' => 'Pegawai Biasa',
                'email' => 'pegawai@example.com',
                'password' => 'password',
                'roles' => ['pegawai'],
                'nip' => '199105051991051001',
                'jabatan' => 'Pegawai Statistik',
                'unit_kerja' => 'Statistik Produksi',
                'phone' => '081234567894',
                'is_active' => true,
            ],
            [
                'name' => 'Multi Role User',
                'email' => 'multirole@example.com',
                'password' => 'password',
                'roles' => ['admin_penyedia', 'teknisi'],
                'nip' => '199106061991061001',
                'jabatan' => 'Admin Penyedia & Teknisi',
                'unit_kerja' => 'IT & Penyedia',
                'phone' => '081234567895',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            $username = explode('@', $userData['email'])[0];
            $rolePriority = ['super_admin', 'admin_layanan', 'admin_penyedia', 'teknisi', 'pegawai'];
            $primaryRole = 'pegawai'; // Fallback default

            foreach ($rolePriority as $role) {
                if (in_array($role, $userData['roles'])) {
                    $primaryRole = $role;
                    break;
                }
            }

            // 3. Simpan / Update User
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
