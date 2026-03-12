<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => ServiceCategory::orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * METHOD BARU: Menampilkan detail satu layanan
     * Ini yang menyebabkan Error 500 sebelumnya (karena method ini tidak ada)
     */
    public function show(ServiceCategory $serviceCategory)
    {
        return response()->json([
            'data' => $serviceCategory
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:booking,repair,general',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',

            'handling_role' => 'nullable|string|exists:roles,code',
            'is_resource_based' => 'boolean',

            'form_schema' => 'nullable|array',
            'action_schema' => 'nullable|array',

            // Konfigurasi Assignment
            'target_role' => 'nullable|string',
            'assignment_type' => 'required|in:auto,manual,direct',
            'default_assignee_id' => 'nullable|required_if:assignment_type,direct|exists:users,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Pastikan default_assignee_id null jika bukan direct
        if ($validated['assignment_type'] !== 'direct') {
            $validated['default_assignee_id'] = null;
        }

        if (empty($validated['handling_role'])) {
            $validated['handling_role'] = 'admin_layanan';
        }

        $category = ServiceCategory::create($validated);

        return response()->json(['message' => 'Layanan berhasil dibuat', 'data' => $category], 201);
    }

    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:booking,service,repair',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
            'is_resource_based' => 'boolean',
            'form_schema' => 'nullable|array',
            'action_schema' => 'nullable|array',
            'target_role' => 'nullable|string',
            'assignment_type' => 'in:auto,manual,direct',
            'default_assignee_id' => 'nullable|required_if:assignment_type,direct|exists:users,id',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Reset assignee jika type berubah bukan direct
        if (isset($validated['assignment_type']) && $validated['assignment_type'] !== 'direct') {
            $validated['default_assignee_id'] = null;
        }

        $serviceCategory->update($validated);

        return response()->json([
            'message' => 'Layanan berhasil diperbarui',
            'data' => $serviceCategory
        ]);
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        return response()->json(['message' => 'Layanan berhasil dihapus']);
    }
}
