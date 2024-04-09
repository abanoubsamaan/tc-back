<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\PurchaseOrderItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
            'po_number' => $this->po_number,
            'buyer_name' => $this->buyer_name,
            'total' => $this->total,
            'date_received' => $this->created_at->format('Y-m-d H:i:s'),
            'date_updated' => $this->updated_at->format('Y-m-d H:i:s'),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
