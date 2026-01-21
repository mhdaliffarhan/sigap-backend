<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use App\Models\Resource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class DynamicServiceController extends Controller
{
    // 1. List semua layanan untuk Menu Utama
    public function index()
    {
        $services = ServiceCategory::where('is_active', true)
            ->select('id', 'name', 'slug', 'type', 'icon', 'description')
            ->get();

        return response()->json(['data' => $services]);
    }

    // 2. Ambil detail layanan + Form Schema-nya
    public function show($slug)
    {
        $service = ServiceCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json(['data' => $service]);
    }

    // 3. Ambil daftar Resource (Stok) berdasarkan layanan
    // Bisa filter tanggal untuk cek availability (Mobil/Ruangan)
    public function getResources(Request $request, $slug)
    {
        $service = ServiceCategory::where('slug', $slug)->firstOrFail();

        $query = Resource::where('service_category_id', $service->id)
            ->where('is_active', true);

        // Fitur Cek Bentrok Jadwal (Universal)
        if ($request->has(['start_date', 'end_date'])) {
            $start = $request->start_date;
            $end = $request->end_date;

            // Cari resource ID yang SUDAH DIBOOKING di jam tersebut
            $bookedResourceIds = Ticket::where('service_category_id', $service->id)
                ->where('status', '!=', 'rejected') // Hiraukan yang ditolak
                ->where('status', '!=', 'completed') // Opsional: kalau selesai dianggap free
                ->whereNotNull('resource_id')
                ->where(function ($q) use ($start, $end) {
                    // Logic: (Start A < End B) AND (End A > Start B) = Overlap
                    $q->where('start_date', '<', $end)
                        ->where('end_date', '>', $start);
                })
                ->pluck('resource_id');

            // Exclude resource yang bentrok
            $query->whereNotIn('id', $bookedResourceIds);
        }

        $resources = $query->get();

        return response()->json(['data' => $resources]);
    }
}
