<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $data = PurchaseOrder::paginate(10);
        return PurchaseOrderResource::collection($data);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return new PurchaseOrderResource($purchaseOrder);
    }

    public function save()
    {

    }
    public function update()
    {

    }
}
