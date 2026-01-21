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
        $validated = $request->validate([
            'code' => ['required', 'string', 'alpha_dash', Rule::unique('roles')->ignore($role->id)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role->update($validated);

        return response()->json(['message' => 'Role berhasil diupdate', 'data' => $role]);
    }

    // Hapus role
    public function destroy(Role $role)
    {
        // Opsional: Cek apakah role sedang dipakai user sebelum hapus
        $role->delete();
        return response()->json(['message' => 'Role berhasil dihapus']);
    }
}
