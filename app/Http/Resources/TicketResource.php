<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'severity' => $this->severity,

            // --- FIELD BARU UNTUK LAYANAN DINAMIS ---
            'service_category_id' => $this->service_category_id,
            'service_category' => $this->whenLoaded('serviceCategory'),
            'resource_id' => $this->resource_id,
            'resource' => $this->whenLoaded('resource'),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'dynamic_form_data' => $this->dynamic_form_data, // <-- Ini isian form (supir, penumpang, dll)
            'action_data' => $this->action_data,
            'action_data' => $this->action_data,
            'current_assignee_role' => $this->current_assignee_role,
            // ----------------------------------------

            // Field Legacy (Perbaikan & Zoom)
            'kode_barang' => $this->kode_barang,
            'nup' => $this->nup,
            'asset_location' => $this->asset_location,
            'zoom_date' => $this->zoom_date,
            'zoom_start_time' => $this->zoom_start_time,
            'zoom_end_time' => $this->zoom_end_time,
            'zoom_link' => $this->zoom_meeting_link,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations
            'user' => new UserResource($this->whenLoaded('user')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'attachments' => $this->attachments,
            'timeline' => TimelineResource::collection($this->whenLoaded('timeline')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }

    private function getButtonStatus()
    {
        // Tiket yang sudah rejected atau closed tidak bisa diubah
        $isClosed = in_array($this->status, ['rejected', 'closed']);

        $diagnosis = $this->diagnosis;

        $hasDiagnosis = $diagnosis !== null;
        $repairType = $diagnosis?->repair_type;
        $needsWorkOrder = in_array($repairType, ['need_sparepart', 'need_vendor', 'need_license']);
        $canBeCompleted = in_array($repairType, ['direct_repair', 'unrepairable']);

        // Get work orders
        $workOrders = $this->workOrders ?? [];
        $allWorkOrdersDelivered = count($workOrders) > 0
            ? collect($workOrders)->every(fn($wo) => in_array($wo->status, ['delivered', 'completed', 'failed', 'cancelled']))
            : true;

        // Check work_orders_ready flag
        $workOrdersReady = $this->work_orders_ready ?? false;

        return [
            'ubahDiagnosis' => [
                'enabled' => !$isClosed,
                'reason' => $isClosed ? 'Tiket sudah ditutup' : null,
            ],
            'workOrder' => [
                'enabled' => !$isClosed && $hasDiagnosis && $needsWorkOrder,
                'reason' => $isClosed ? 'Tiket sudah ditutup' : (!$hasDiagnosis ? 'Diagnosis belum diisi' : (!$needsWorkOrder ? 'Diagnosis tidak memerlukan work order' : null)),
            ],
            'selesaikan' => [
                'enabled' => !$isClosed && $hasDiagnosis && (!$needsWorkOrder || $workOrdersReady),
                'reason' => $isClosed ? 'Tiket sudah ditutup' : (!$hasDiagnosis ? 'Diagnosis belum diisi' : ($needsWorkOrder && !$workOrdersReady ? 'Klik "Lanjutkan Perbaikan" setelah work order selesai' : null)),
            ],
        ];
    }

    /**
     * Transform attachments array to ensure correct URL with proper encoding
     */
    private function transformAttachments(array $attachments): array
    {
        return collect($attachments)->map(function ($attachment) {
            // Regenerate URL from path to ensure correct APP_URL and proper encoding
            if (isset($attachment['path'])) {
                // Split path into directory and filename
                $pathParts = explode('/', $attachment['path']);
                $filename = array_pop($pathParts);
                $directory = implode('/', $pathParts);

                // Encode filename to handle spaces and special characters
                $encodedFilename = rawurlencode($filename);
                $encodedPath = $directory ? $directory . '/' . $encodedFilename : $encodedFilename;

                $attachment['url'] = config('app.url') . '/storage/' . $encodedPath;
            }
            return $attachment;
        })->toArray();
    }
}
