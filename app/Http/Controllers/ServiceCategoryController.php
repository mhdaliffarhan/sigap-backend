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
            'form_schema' => 'nullable|array',
            // Validasi dalam schema jika perlu
            // 'form_schema.*.name' => 'required|string', 
        ]);

        $validated['slug'] = Str::slug($validated['name']);

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
            'form_schema' => 'nullable|array',
        ]);

        if ($request->has('name')) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $serviceCategory->update($validated);

        return response()->json(['message' => 'Layanan berhasil diperbarui', 'data' => $serviceCategory]);
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        return response()->json(['message' => 'Layanan berhasil dihapus']);
    }
}
