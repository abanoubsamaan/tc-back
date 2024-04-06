<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $fillable = ['po_number', 'buyer_name', 'total'];

    public function items(){
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }
}
