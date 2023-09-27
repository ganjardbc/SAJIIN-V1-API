<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseOrderItems extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => '1',
                'toping_price' => 2000,
                'price' => 150000,
                'discount' => 0,
                'quantity' => 1,
                'subtotal' => 152000,
                'product_name' => 'Lorem ipsum dolor ismet',
                'product_detail' => 'SIZE S',
                'product_toping' => 'Cheese',
                'order_id' => 1,
                'product_id' => 1,
                'proddetail_id' => 1,
                'toping_id' => 1,
                'status' => 'waiting',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ],
            [
                'id' => '2',
                'toping_price' => 2000,
                'price' => 200000,
                'discount' => 0,
                'quantity' => 2,
                'subtotal' => 404000,
                'product_name' => 'Lorem ipsum dolor ismet',
                'product_detail' => 'SIZE M',
                'product_toping' => 'Caramel',
                'order_id' => 1,
                'product_id' => 1,
                'proddetail_id' => 3,
                'toping_id' => 2,
                'status' => 'waiting',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ],
            [
                'id' => '3',
                'toping_price' => 2000,
                'price' => 150000,
                'discount' => 0,
                'quantity' => 2,
                'subtotal' => 304000,
                'product_name' => 'Lorem ipsum dolor ismet',
                'product_detail' => 'SIZE S',
                'product_toping' => 'Strawberry',
                'order_id' => 2,
                'product_id' => 1,
                'proddetail_id' => 1,
                'toping_id' => 3,
                'status' => 'waiting',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ],
        ];

        DB::table('order_items')->insert($data);
    }
}
