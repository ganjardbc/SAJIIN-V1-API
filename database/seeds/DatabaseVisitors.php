<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseVisitors extends Seeder
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
                'start_date' => '2020-06-28 15:08:45',
                'end_date' => '2020-06-28 19:08:45',
                'table_id' => 1,
                'owner_id' => 1,
                'user_id' => 1,
                'status' => 'inactive',
                'created_by' => '1',
                'updated_by' => '1',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ],
            [
                'id' => '2',
                'start_date' => '2020-06-28 15:08:45',
                'end_date' => '2020-06-28 19:08:45',
                'table_id' => 2,
                'owner_id' => 2,
                'user_id' => 1,
                'status' => 'inactive',
                'created_by' => '1',
                'updated_by' => '1',
                "created_at" => '2020-06-28 19:08:45',
                "updated_at" => '2020-06-28 19:08:45'
            ],
            [
                'id' => '3',
                'start_date' => '2020-05-11 15:08:45',
                'end_date' => null,
                'table_id' => 1,
                'owner_id' => 2,
                'user_id' => 1,
                'status' => 'active',
                'created_by' => '1',
                'updated_by' => '1',
                "created_at" => '2020-05-11 19:08:45',
                "updated_at" => '2020-05-11 19:08:45'
            ]
        ];

        DB::table('visitors')->insert($data);
    }
}
