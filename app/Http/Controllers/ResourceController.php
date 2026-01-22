<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    // Ambil resource berdasarkan kategori layanan
    public function index(Request $request)
    {
        $query = Resource::query();

        if ($request->has('service_category_id')) {
            $query->where('service_category_id', $request->service_category_id);
        }

        return response()->json([
            'data' => $query->orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer', // Misal: Kapasitas kursi mobil/ruangan
            'is_active' => 'boolean'
        ]);

        $resource = Resource::create($validated);

        return response()->json(['message' => 'Resource berhasil ditambahkan', 'data' => $resource], 201);
    }

    public function update(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        $resource->update($validated);

        return response()->json(['message' => 'Resource berhasil diperbarui', 'data' => $resource]);
    }

    public function destroy(Resource $resource)
    {
        $resource->delete();
        return response()->json(['message' => 'Resource berhasil dihapus']);
    }
}
