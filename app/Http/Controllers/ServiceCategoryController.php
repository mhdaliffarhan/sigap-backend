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
            'type' => 'required|in:booking,service,repair',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',

            'handling_role' => 'nullable|string|exists:roles,code',
            'is_resource_based' => 'boolean',

            'form_schema' => 'nullable|array',
            'action_schema' => 'nullable|array',
            // Validasi dalam schema jika perlu
            // 'form_schema.*.name' => 'required|string', 
        ]);

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
        ]);

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
