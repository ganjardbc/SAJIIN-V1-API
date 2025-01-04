<?php

namespace App\Services;

use App\ZLogOrder;

class LogService 
{
    /**
     * Create a log entry in z_log_orders.
     *
     * @param string $logMessage
     * @param int $shopId
     * @param int $userId
     * @return void
     */
    public function createLog(string $logMessage, int $shopId, int $userId): void
    {
        // Prepare data for the log
        $data = [
            'log' => $logMessage,
            'shop_id' => $shopId,
            'user_id' => $userId,
            'time' => now(),
        ];

        // Insert log entry
        ZLogOrder::create($data);
    }
}