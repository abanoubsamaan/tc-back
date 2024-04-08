<?php

namespace Feature;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations(): void
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    /**
     * @return void
     */
    public function test_index_returns_correct_response(): void
    {
        $response = $this->get('/api/purchase-orders');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'po_number', 'buyer_name', 'date_received', 'date_updated'
                ]
            ], 'links', 'meta'
        ]);
    }

    /**
     * @return void
     */
    public function test_show_returns_correct_response(): void
    {
        $response = $this->get('/api/purchase-orders/1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id', 'po_number', 'buyer_name', 'date_received', 'date_updated', 'items' => [
                    '*' => [
                        'id', 'description', 'quantity', 'unit_price', 'category' => ['id', 'name']
                    ]
                ]
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_show_returns_not_found(): void
    {
        $response = $this->get('/api/purchase-orders/xxx');
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Record not found.']);
    }

    /**
     * @dataProvider storePurchaseOrderSuccessfulDataProvider
     * @return void
     */
    public function test_store_stores_the_purchase_order_successfully(array $assertions, array $expected): void
    {
        $response = $this->post('/api/purchase-orders', $assertions);
        $response->assertStatus(200);
        $this->assertDatabaseHas('purchase_orders', ['po_number' => $expected['po_number']]);
        $this->assertDatabaseCount('purchase_orders',$expected['po_count']);
        $this->assertDatabaseCount('purchase_order_items',$expected['po_items_count']);
    }

    /**
     * @return array[]
     */
    public static function storePurchaseOrderSuccessfulDataProvider(): array
    {
        return [
            'with_single_item' => [
                'assertions' => [
                    'po_number' => '123', 'buyer_name' => 'abanoub', 'items' => [
                        [
                            'description' => 'some text', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 1
                        ]
                    ]
                ],
                'expected' => [
                    'po_number' => '123',
                    'po_count' => 21,
                    'po_items_count' => 61,
                ]
            ],
            'with_multiple_items' => [
                'assertions' => [
                    'po_number' => '456', 'buyer_name' => 'abanoub', 'items' => [
                        ['description' => 'some text', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 1],
                        ['description' => 'some text 2', 'quantity' => 2, 'unit_price' => 5, 'category_id' => 1],
                    ]
                ],
                'expected' => [
                    'po_number' => '456',
                    'po_count' => 21,
                    'po_items_count' => 62,
                ]
            ]
        ];
    }

    /**
     * @dataProvider storePurchaseOrderValidationErrorsDataProvider
     * @param  array  $assertions
     * @param  array  $expected
     * @return void
     */
    public function test_store_returns_validation_errors(array $assertions, array $expected)
    {
        $response = $this->post('/api/purchase-orders', $assertions);
        $response->assertStatus($expected['status_code']);
        $response->assertJsonStructure([
            'message', 'details' => [$expected['field']]
        ]);
    }

    /**
     * @return array
     */
    public static function storePurchaseOrderValidationErrorsDataProvider(): array
    {
        return [
            'without_po_number' => [
                'assertions' => [
                    'buyer_name' => 'abanoub', 'items' => [
                        [
                            'description' => 'some text', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 1
                        ]
                    ]
                ], 'expected' => [
                    'status_code' => 422, 'field' => 'po_number',
                ],
            ],
            'without_buyer_name' => [
                'assertions' => [
                    'po_number' => '123', 'items' => [
                        [
                            'description' => 'some text', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 1
                        ]
                    ]
                ], 'expected' => [
                    'status_code' => 422, 'field' => 'buyer_name',
                ],
            ],
            'without_items' => [
                'assertions' => ['po_number' => '123', 'buyer_name' => 'test'], 'expected' => [
                    'status_code' => 422, 'field' => 'items',
                ],
            ],
            'without_item_description' => [
                'assertions' => [
                    'po_number' => '123', 'buyer_name' => 'test', 'items' => [
                        [
                            'quantity' => 1, 'unit_price' => 10, 'category_id' => 1
                        ]
                    ]
                ],
                'expected' => [
                    'status_code' => 422, 'field' => 'items.0.description',
                ],
            ],
        ];
    }

    /**
     * @dataProvider updatePurchaseOrderSuccessfulDataProvider
     * @param  array  $assertions
     * @param  array  $expected
     * @return void
     */
    public function test_update_updates_the_purchase_order_successfully(array $assertions, array $expected)
    {
        $response = $this->patch('/api/purchase-orders/1', $assertions);
        $response->assertStatus($expected['status_code']);
        $updatedPurchaseOrder = PurchaseOrder::find(1);

        $this->assertEquals($updatedPurchaseOrder->po_number, $expected['purchase_order']['po_number']);
        $this->assertEquals($updatedPurchaseOrder->buyer_name, $expected['purchase_order']['buyer_name']);
        $this->assertEquals($updatedPurchaseOrder->total, $expected['purchase_order']['total']);
        $this->assertCount($expected['purchase_order']['total_items_count'], $updatedPurchaseOrder->items);

        foreach($updatedPurchaseOrder->items as $index => $item){
            $this->assertEquals($item->description, $expected['purchase_order']['items'][$index]['description']);
            $this->assertEquals($item->quantity, $expected['purchase_order']['items'][$index]['quantity']);
            $this->assertEquals($item->unit_price, $expected['purchase_order']['items'][$index]['unit_price']);
            $this->assertEquals($item->category_id, $expected['purchase_order']['items'][$index]['category_id']);
        }
    }

    /**
     * @return array[]
     */
    public static function updatePurchaseOrderSuccessfulDataProvider(): array
    {
        return [
            'update_po_and_add_item' => [
                'assertions' => [
                    'po_number' => '123-updated', 'buyer_name' => 'name-updated', 'items' => [
                        [
                            'id' => 1, 'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                        ],
                        [
                            'description' => 'new item', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 1
                        ]
                    ]
                ], 'expected' => [
                    'status_code' => 200,
                    'purchase_order' => [
                        'po_number' => '123-updated',
                        'buyer_name' => 'name-updated',
                        'total' => 50,
                        'total_items_count' => 2,
                        'items' => [
                            [
                                'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                            ],
                            [
                                'description' => 'new item', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 1
                            ]
                        ]
                    ],

                ],
            ],
            'update_po_and_existing_items' => [
                'assertions' => [
                    'po_number' => '123-updated', 'buyer_name' => 'name-updated', 'items' => [
                        [
                            'id' => 1, 'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                        ],
                        [
                            'id' => 2, 'description' => 'some text updated', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 2
                        ],
                        [
                            'id' => 3, 'description' => 'some text updated', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 2
                        ]
                    ]
                ],
                'expected' => [
                    'status_code' => 200,
                    'purchase_order' => [
                        'po_number' => '123-updated',
                        'buyer_name' => 'name-updated',
                        'total' => 60,
                        'total_items_count' => 3,
                        'items' => [
                            [
                                'id' => 1, 'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                            ],
                            [
                                'id' => 2, 'description' => 'some text updated', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 2
                            ],
                            [
                                'id' => 3, 'description' => 'some text updated', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 2
                            ]
                        ]
                    ],

                ],
            ],
            'update_po_and_existing_items_and_delete_items' => [
                'assertions' => [
                    'po_number' => '123-updated', 'buyer_name' => 'name-updated', 'items' => [
                        [
                            'id' => 1, 'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                        ]
                    ]
                ], 'expected' => [
                    'status_code' => 200,
                    'purchase_order' => [
                        'po_number' => '123-updated',
                        'buyer_name' => 'name-updated',
                        'total' => 40,
                        'total_items_count' => 1,
                        'items' => [
                            [
                                'id' => 1, 'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                            ]
                        ]
                    ],

                ],
            ],
        ];
    }

    /**
     * @dataProvider updatePurchaseOrderValidationErrorsDataProvider
     * @param  array  $assertions
     * @param  array  $expected
     * @return void
     */
    public function test_update_purchase_order_returns_validation_errors(array $assertions, array $expected): void
    {
        $response = $this->patch('/api/purchase-orders/1', $assertions);
        $response->assertStatus($expected['status_code']);
        $response->assertJsonStructure([
            'message', 'details' => $expected['fields']
        ]);
    }

    public static function updatePurchaseOrderValidationErrorsDataProvider(): array
    {
        return [
            'all_is_missing' => [
                'assertions' => [],
                'expected' => [
                    'status_code' => 422,
                    'fields' => ['po_number', 'buyer_name', 'items']
                ],
            ],
            'without_items_and_without_po_number' => [
                'assertions' => ['buyer_name' => 'name-updated'],
                'expected' => [
                    'status_code' => 422,
                    'fields' => ['items', 'po_number']
                ],
            ],
            'without_items' => [
                'assertions' => ['po_number' => '123-updated', 'buyer_name' => 'name-updated'],
                'expected' => [
                    'status_code' => 422,
                    'fields' => ['items']
                ],
            ],
            'without_first_item_description' => [
                'assertions' => ['po_number' => '123-updated', 'buyer_name' => 'name-updated', 'items' => [
                    [
                        'id' => 1,  'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                    ],
                    [
                        'id' => 2, 'description' => 'some text updated', 'quantity' => 1, 'unit_price' => 10, 'category_id' => 2
                    ],
                ]],
                'expected' => [
                    'status_code' => 422,
                    'fields' => ['items.0.description']
                ],
            ],
            'without_second_item_quantity' => [
                'assertions' => ['po_number' => '123-updated', 'buyer_name' => 'name-updated', 'items' => [
                    [
                        'id' => 1, 'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                    ],
                    [
                        'id' => 2, 'description' => 'some text updated', 'unit_price' => 10, 'category_id' => 2
                    ],
                ]],
                'expected' => [
                    'status_code' => 422,
                    'fields' => ['items.1.quantity']
                ],
            ],
            'with_empty_unit_price_in_the_second_item' => [
                'assertions' => ['po_number' => '123-updated', 'buyer_name' => 'name-updated', 'items' => [
                    [
                        'id' => 1, 'description' => 'some text updated', 'quantity' => 2, 'unit_price' => 20, 'category_id' => 2
                    ],
                    [
                        'id' => 2, 'description' => 'some text updated', 'unit_price' => '', 'category_id' => 2
                    ],
                ]],
                'expected' => [
                    'status_code' => 422,
                    'fields' => ['items.1.unit_price']
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function test_destroy_deletes_purchase_order_successfully(): void
    {
        $response = $this->delete('/api/purchase-orders/1');
        $response->assertStatus(200);
        $this->assertDatabaseMissing('purchase_orders',['id' => 1]);
        $this->assertDatabaseMissing('purchase_order_items',['purchase_order_id' => 1]);
    }

    /**
     * @return void
     */
    public function test_destroy_returns_not_found(): void
    {
        $response = $this->delete('/api/purchase-orders/xx');
        $response->assertStatus(404);
    }

    /**
     * @dataProvider destroyManyDeletesPurchaseOrdersDataProvider
     * @param  array  $assertions
     * @return void
     */
    public function test_destroy_many_deletes_purchase_orders_successfully(array $assertions): void
    {
        $response = $this->delete('/api/purchase-orders/delete', $assertions);
        $response->assertStatus(200);

        foreach($assertions['ids'] as $id){
            $this->assertDatabaseMissing('purchase_orders', ['id' => $id]);
            $this->assertDatabaseMissing('purchase_order_items',['purchase_order_id' => $id]);
        }
    }

    /**
     * @return \array[][]
     */
    public static function destroyManyDeletesPurchaseOrdersDataProvider(): array
    {
        return [
            'single_purchase_order' => [
                'assertions' => [
                    'ids' => [1]
                ]
            ],
            'multiple_purchase_orders' => [
                'assertions' => [
                    'ids' => [1,2]
                ]
            ],
        ];
    }

    /**
     * @return void
     */
    public function test_destroy_many_rolls_back_if_any_of_the_given_ids_wrong(): void
    {
        $response = $this->delete('/api/purchase-orders/delete', ['ids' => [1,2,'x']]);
        $response->assertStatus(404);
        $this->assertDatabaseHas('purchase_orders', ['id' => 1]);
        $this->assertDatabaseHas('purchase_orders', ['id' => 2]);

        $this->assertDatabaseHas('purchase_order_items',['purchase_order_id' => 1]);
        $this->assertDatabaseHas('purchase_order_items',['purchase_order_id' => 2]);

    }

}
