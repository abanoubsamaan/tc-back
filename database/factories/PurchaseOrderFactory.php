<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'po_number' => fake()->numberBetween('10000','1000000000'),
            'buyer_name' => fake()->company,
            'total' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (PurchaseOrder $purchaseOrder) {
            // Create three purchase order items with unique attributes
            for ($i = 0; $i < 3; $i++) {
                PurchaseOrderItem::factory()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'description' => fake()->sentence,
                    'quantity' => $this->faker->randomNumber(1, 100),
                    'unit_price' => $this->faker->randomFloat('2',1,1000),
                ]);
            }

            // Calculate total and update the purchase order
            $total = $purchaseOrder->items->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });

            $purchaseOrder->update(['total' => $total]);
        });
    }
}
