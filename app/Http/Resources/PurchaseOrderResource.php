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
            'date_received' => $this->created_at,
            'date_updated' => $this->updated_at,
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
