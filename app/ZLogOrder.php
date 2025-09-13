<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZLogOrder extends Model
{
    // Specify the table name
    protected $table = 'z_log_orders';

    // Disable timestamps (optional, since `time` is already set manually)
    public $timestamps = false;

    // Define the fillable fields for mass assignment
    protected $fillable = ['time', 'log', 'shop_id', 'user_id'];
}
