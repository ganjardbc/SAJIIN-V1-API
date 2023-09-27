<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseCategories extends Seeder
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
                'category_id' => 'CT0001',
                'name' => 'WEEDING CAKES',
                'description' => 'Lorem ipsum dolor ismet.',
                'status' => 'active',
                'user_id' => '1',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ],
            [
                'id' => '2',
                'category_id' => 'CT0002',
                'name' => 'DESERT CAKES',
                'description' => 'Lorem ipsum dolor ismet.',
                'status' => 'active',
                'user_id' => '1',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ],
            [
                'id' => '3',
                'category_id' => 'CT0003',
                'name' => 'CELEBRATION CAKES',
                'description' => 'Lorem ipsum dolor ismet.',
                'status' => 'active',
                'user_id' => '1',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ]
        ];

        DB::table('categories')->insert($data);
    }
}
