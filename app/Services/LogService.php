<?php

namespace App\Services;

use App\ZLogOrder;

class LogService 
{

    const ALLOWED_TABLES = ['z_log_orders'];

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


    public function getLogByTable(string $tableName, ?string $startDate = null, ?string $endDate = null): array
    {
        // Validate the table name to prevent SQL injection
        if (!in_array($tableName, self::ALLOWED_TABLES, true)) {
            throw new \Exception("Invalid table name: {$tableName}");
        }

        // Build the query
        $query = DB::table($tableName);

        // Apply date range filter if provided
        if ($startDate && $endDate) {
            $query->whereBetween('time', [$startDate, $endDate]);
        }

        // Retrieve the logs
        return $query->get()->toArray();

    }

    public function getTotalLogCount($tableName, $startDate, $endDate)
    {
        // Count the total number of records in the specified table for the given date range
        return DB::table($tableName)
            ->whereBetween('time', [$startDate, $endDate])
            ->count();
    }

}