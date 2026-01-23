<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Resource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
  /**
   * Cek jadwal resource tertentu dalam rentang waktu
   */
  public function check(Request $request)
  {
    $request->validate([
      'resource_id' => 'required|exists:resources,id',
      'start_date' => 'required|date',
      'end_date' => 'required|date|after:start_date',
    ]);

    $start = Carbon::parse($request->start_date);
    $end = Carbon::parse($request->end_date);

    // Cari tiket yang BENTROK
    // Logika Overlap: (StartA <= EndB) and (EndA >= StartB)
    $conflictingTicket = Ticket::where('resource_id', $request->resource_id)
      ->where('status', '!=', 'cancelled') // Abaikan tiket batal
      ->where('status', '!=', 'rejected')  // Abaikan tiket tolak
      ->where(function ($query) use ($start, $end) {
        $query->whereBetween('start_date', [$start, $end])
          ->orWhereBetween('end_date', [$start, $end])
          ->orWhere(function ($q) use ($start, $end) {
            $q->where('start_date', '<=', $start)
              ->where('end_date', '>=', $end);
          });
      })
      ->first();

    if ($conflictingTicket) {
      return response()->json([
        'available' => false,
        'message' => 'Jadwal bentrok dengan tiket lain',
        'conflict_with' => $conflictingTicket->ticket_number
      ]);
    }

    return response()->json([
      'available' => true,
      'message' => 'Jadwal tersedia'
    ]);
  }

  /**
   * Ambil semua jadwal terisi untuk resource tertentu (Untuk Kalender View)
   */
  public function getEvents(Request $request, $resourceId)
  {
    // Ambil data bulan ini (opsional filter month/year)
    $events = Ticket::where('resource_id', $resourceId)
      ->whereNotIn('status', ['cancelled', 'rejected'])
      ->get(['id', 'title', 'start_date', 'end_date', 'user_id', 'status']);

    return response()->json($events);
  }
}
