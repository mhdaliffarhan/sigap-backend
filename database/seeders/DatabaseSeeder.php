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
        // 1. Definisikan list role dan simpan ke tabel roles
        $this->call(RoleSeeder::class);
        
        // 2. Buat master user (termasuk super admin)
        $this->call(UserSeeder::class);
        
        // Untuk data seeders lainnya bisa diletakkan di bawah ini
    }
}
