<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    public function scopeGetCount($query, $id)
    {
        return $this
        ->select( 'id' )
        ->where(['shop_id' => $id, 'is_read' => 0])
        ->count();
    }
}
