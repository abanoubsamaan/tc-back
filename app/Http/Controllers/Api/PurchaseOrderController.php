<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePurchaseOrderRequest;
use App\Http\Requests\Api\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Item;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PurchaseOrderController extends Controller
{
    /**
     * Get all purchase paginated in descending order
     * @return AnonymousResourceCollection
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): AnonymousResourceCollection
    {
        $purchaseOrders = PurchaseOrder::query();
        $search = request()->get('search');
        if($search) {
            $purchaseOrders = $purchaseOrders->where('po_number','like','%'.$search.'%')->
                orWhere('buyer_name','like','%'.$search.'%');
        }
        $purchaseOrders = $purchaseOrders->orderBy('id', 'desc')->paginate(10);


        return PurchaseOrderResource::collection($purchaseOrders);
    }

    /**
     * Get single purchase order with the related items
     * @param  PurchaseOrder  $purchaseOrder
     * @return PurchaseOrderResource
     */
    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($purchaseOrder->load('items'));
    }

    /**
     * Create new purchase order with items
     * @param  StorePurchaseOrderRequest  $request
     * @return JsonResponse
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        // Use DB transactions to ensure that items are inserted with the order
        DB::transaction(function () use ($request) {
            // Calculate order total before inserting the order itself to avoid one more query
            $total = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            // Create purchase order
            $order = PurchaseOrder::create($request->except(['items']) + ['total' => $total]);

            // Create purchase order items
            $order->items()->createMany($request->only(['items'])['items']);
        });

        return response()->json(['message' => 'Purchase order created successfully!']);
    }

    /**
     * Update the given purchase order in the following way
     * - All purchase order information are optional (if field doesn't exist in the request, then it will stay as is)
     * - If the item has an id, item information will be updated
     * - If the item doesn't have ID, it will be considered as a new item
     * - Delete the items that not exist in request
     * - Making sure that the Total value in the purchase order is updated accordingly
     * @param  UpdatePurchaseOrderRequest  $request
     * @param  PurchaseOrder  $purchaseOrder
     * @return JsonResponse
     */
    public function old_update(
        UpdatePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {
        // Wrap all DB queries in a transaction
        DB::transaction(function () use ($request, $purchaseOrder) {
            // Updated or create items
            if ($request->items) {
                $orderItemIds = $purchaseOrder->items->pluck('id')->toArray();
                foreach ($request->items as $item) {
                    // Updated the Item if given item request has id, and it is one of the order items
                    if (isset($item['id']) && in_array($item['id'], $orderItemIds)) {
                        $purchaseOrder->items()->where('id', $item['id'])->update($item);
                    } else {
                        // Create new item if the id is not given or was not related to the given Purchase order
                        $purchaseOrder->items()->create($item);
                    }
                }
            }

            // Calculate and update the total of the purchase order based on updated items
            $total = $purchaseOrder->items->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });

            $purchaseOrder->update($request->except(['items']) + ['total' => $total]);
        });

        return response()->json(['message' => 'Purchase order updated successfully!']);
    }

    /**
     * Update the given purchase order in the following way
     * - All purchase order information should exist
     * - If the item has an id, item information will be updated
     * - If the item doesn't have an ID, it will be considered as a new item
     * - Delete the items that is not exist in request (except for the new items)
     * - Making sure that the Total value in the purchase order is updated accordingly
     * @param  UpdatePurchaseOrderRequest  $request
     * @param  PurchaseOrder  $purchaseOrder
     * @return JsonResponse
     */
    public function update(
        UpdatePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {
        // Wrap all DB queries in a transaction
        DB::transaction(function () use ($request, $purchaseOrder) {
            // Updated or create items
            $orderItemIds = $purchaseOrder->items->pluck('id')->toArray();
            $requestItemIds = [];
            foreach ($request->items as $item) {
                // Updated the Item if given item request has id, and it is one of the order items
                if (isset($item['id']) && in_array($item['id'], $orderItemIds)) {
                    $requestItemIds[] = $item['id'];
                    $purchaseOrder->items()->where('id', $item['id'])->update(collect($item)->only(['description','quantity','unit_price', 'category_id'])->toArray());
                } else {
                    // Create new item if the id is not given or was not related to the given Purchase order
                    $newItem = $purchaseOrder->items()->create($item);
                    $requestItemIds[] = $newItem->id;
                }
            }
            // Delete items that not exist in the request
            $purchaseOrder->items()->whereNotIn('id', $requestItemIds)->delete();

            // Calculate and update the total of the purchase order based on updated items
            $total = $purchaseOrder->items()->sum(DB::raw('quantity * unit_price'));
            $purchaseOrder->update($request->except(['items','total']) + ['total' => $total]);
        });

        return response()->json(['message' => 'Purchase order updated successfully!']);
    }

    /**
     * Delete single purchase order
     * @param  PurchaseOrder  $purchaseOrder
     * @return JsonResponse
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try{
            $purchaseOrder->delete();
        }catch (\Exception $e){
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Purchase order deleted successfully!']);
    }

    /**
     * Delete multiple purchase orders
     * @param  Request  $request
     * @return JsonResponse
     */
    public function destroyMany(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $deleted = PurchaseOrder::destroy($request->ids);
            if($deleted !== count($request->ids)){
                DB::rollBack();
                return response()->json(['message' => 'One of the given ids is not found'], 404);
            }
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
        DB::commit();

        return response()->json(['message' => 'Purchase orders deleted successfully!']);
    }


}
