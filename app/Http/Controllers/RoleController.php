<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    // List semua role
    public function index()
    {
        return response()->json([
            'data' => Role::orderBy('name')->get()
        ]);
    }

    // Buat role baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:roles,code|alpha_dash', // alpha_dash: admin_ga
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role = Role::create($validated);

        return response()->json(['message' => 'Role berhasil dibuat', 'data' => $role], 201);
    }

    // Update role
    public function update(Request $request, Role $role)
    {
        // Validasi
        $validated = $request->validate([

            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);

        return response()->json(['message' => 'Role berhasil diupdate', 'data' => $role]);
    }

    // Hapus role
    public function destroy(Role $role)
    {
        // 1. Cek apakah role ini adalah role bawaan sistem (hardcoded seeder)?
        //    Sebaiknya role krusial seperti super_admin jangan dihapus.
        $protectedRoles = ['super_admin', 'admin_layanan', 'pegawai', 'teknisi'];
        if (in_array($role->code, $protectedRoles)) {
            return response()->json(['message' => 'Role bawaan sistem tidak boleh dihapus!'], 403);
        }

        // 2. Cek apakah ada User yang menggunakan role ini?
        //    Asumsi kita pakai kolom string 'role' di tabel users
        $userCount = \App\Models\User::where('role', $role->code)
            ->orWhereJsonContains('roles', $role->code) // Jika pakai JSON roles
            ->count();

        if ($userCount > 0) {
            return response()->json([
                'message' => "Gagal hapus! Masih ada $userCount user yang menggunakan role ini. Silakan ganti role user tersebut terlebih dahulu."
            ], 422);
        }

        // 3. Cek apakah ada Workflow Transition yang menggunakan role ini?
        $workflowCount = \Illuminate\Support\Facades\DB::table('workflow_transitions')
            ->where('trigger_role', $role->code)
            ->orWhere('target_assignee_role', $role->code)
            ->count();

        if ($workflowCount > 0) {
            return response()->json([
                'message' => "Gagal hapus! Role ini digunakan dalam $workflowCount aturan workflow. Hapus aturan workflow terlebih dahulu."
            ], 422);
        }

        $role->delete();
        return response()->json(['message' => 'Role berhasil dihapus']);
    }
}
