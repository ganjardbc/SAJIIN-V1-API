<?php

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\Request;

class GenericLogController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function getAuditLogs(Request $request)
    {
        // Validate query parameters
        $request->validate([
            'table_name' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        try {
            // Retrieve logs using the LogService
            $logs = $this->logService->getLogByTable(
                $request->query('table_name'),
                $request->query('startDate'),
                $request->query('endDate')
            );

            $totalRecord = $this->logService->getTotalLogCount(
                $request->query('table_name'),
                $request->query('startDate'),
                $request->query('endDate')
            );

            // Prepare response data
            $response = [
                'message' => 'proceed success',
                'status' => 'ok',
                'code' => '200',
                'data' => $logs,
                'total_record' => $totalRecord,
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            $response = [
                'message' => $e->getMessage(),
                'status' => 'error',
                'code' => '400',
                'data' => [],
                'total_record' => 0,
            ];

            return response()->json($response, 400);
        }
    }

}