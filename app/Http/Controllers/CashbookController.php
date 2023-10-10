<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Cashbook;
use App\Shop;
use App\Order;
use App\ExpenseList;
use Image;
use Carbon\Carbon;

class CashbookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getAll(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'limit' => 'required|integer',
            'offset' => 'required|integer'
        ]);

        $response = [];

        if ($validator->fails()) 
        {
            $response = [
                'message' => $validator->errors(),
                'status' => 'invalide',
                'code' => '201',
                'data' => []
            ];
        } 
        else 
        {
            $search = $req['search'];
            $limit = $req['limit'];
            $offset = $req['offset'];
            $status = $req['status'] ? ['status' => $req['status']] : [];
            $data = [];
            $totalRecord = 0;

            $newStt = $status;
            if ($req['shop_id']) {
                $newStt = array_merge($status, ['shop_id' => $req['shop_id']]);
            }
            $data = Cashbook::where($newStt)
                ->where(function ($query) use ($search) {
                    $query->where('cashbook_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('cash_date', 'LIKE', '%'.$search.'%')
                        ->orWhere('cash_modal', 'LIKE', '%'.$search.'%');
                })
                ->limit($limit)
                ->offset($offset)
                ->orderBy('cash_date', 'desc')
                ->get();
            $totalRecord = Cashbook::where($newStt)
                ->where(function ($query) use ($search) {
                    $query->where('cashbook_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('cash_date', 'LIKE', '%'.$search.'%')
                        ->orWhere('cash_modal', 'LIKE', '%'.$search.'%');
                })
                ->count();
            
            if ($data) 
            {
                $newPayload = array();

                $dump = json_decode($data, true);
                
                for ($i=0; $i < count($dump); $i++) { 
                    $cashbook = $dump[$i];
                    $shop = Shop::where('id', $cashbook['shop_id'])->first();

                    // order
                    $order_progress = Order::where('shop_id', $req['shop_id'])
                        ->where('cashbook_id', $cashbook['id'])
                        ->where('status', '!=', 'done')
                        ->where('status', '!=', 'canceled')
                        ->count();
                    $order_done = Order::where('shop_id', $req['shop_id'])
                        ->where('cashbook_id', $cashbook['id'])
                        ->where('status', 'done')
                        ->count();
                    $order_total = Order::where('shop_id', $req['shop_id'])
                        ->where('cashbook_id', $cashbook['id'])
                        ->where('status', '!=', 'canceled')
                        ->count();
                    $cashbook['order_progress'] = $order_progress;
                    $cashbook['order_done'] = $order_done;
                    $cashbook['order_total'] = $order_total;

                    // counting temporary
                    $cash_in = Order::where('shop_id', $req['shop_id'])
                        ->where('cashbook_id', $cashbook['id'])
                        ->where('payment_status', true)
                        ->where('status', '!=', 'canceled')
                        ->sum('total_price');
                    // $cash_out_order = Order::where('shop_id', $req['shop_id'])
                    //     ->where('cashbook_id', $cashbook['id'])
                    //     ->where('payment_status', true)
                    //     ->where('status', '!=', 'canceled')
                    //     ->sum('change_price');
                    $cash_expense = ExpenseList::where('shop_id', $req['shop_id'])
                        ->where('cashbook_id', $cashbook['id'])
                        ->where('status', 'active')
                        ->sum('expense_price');
                    // $cash_out = $cash_out_order + $cash_expense;
                    $cash_out = $cash_expense;
                    $cash_modal = $cashbook['cash_modal'];
                    $cash_summary = ($cash_modal + $cash_in) - $cash_out;
                    $cash_profit = $cash_summary - $cash_modal;
                    $cashbook['cash_summary'] = (int)$cash_summary;
                    $cashbook['cash_in'] = (int)$cash_in;
                    $cashbook['cash_out'] = (int)$cash_out;
                    $cashbook['cash_profit'] = (int)$cash_profit;

                    $payload = [
                        'cashbook' => $cashbook,
                        'shop' => $shop
                    ];
                    array_push($newPayload, $payload);
                }

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $newPayload,
                    'total_record' => $totalRecord
                ];
            } 
            else 
            {
                $response = [
                    'message' => 'failed to get datas',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => [],
                    'total_record' => $totalRecord
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function getByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'cashbook_id' => 'required|string|min:0',
        ]);

        $response = [];

        if ($validator->fails()) 
        {
            $response = [
                'message' => $validator->errors(),
                'status' => 'invalide',
                'code' => '201',
                'data' => []
            ];
        } 
        else 
        {
            $cashbook_id = $req['cashbook_id'];
            $data = Cashbook::where(['cashbook_id' => $cashbook_id])->first();
            
            if ($data) 
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $data
                ];
            } 
            else 
            {
                $response = [
                    'message' => 'failed to get datas',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function getCurrent(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shop_id' => 'required|integer',
            'date' => 'required|string|min:0',
        ]);

        $response = [];

        if ($validator->fails()) 
        {
            $response = [
                'message' => $validator->errors(),
                'status' => 'invalide',
                'code' => '201',
                'data' => []
            ];
        } 
        else 
        {
            $payload = [];
            $cash_in = 0;
            $cash_out = 0;
            $total_item = 0;
            $total_order = 0;

            $date = $req['date'].' 00:00:01';
            $shop_id = $req['shop_id'];

            $shop = Shop::where('id', $shop_id)->first();
            // where('cash_date', $date)
            $current_cashbook = Cashbook::where('cash_status', 'open')
                ->where('status', 'active') 
                ->where('shop_id', $shop_id)
                ->orderBy('cash_date', 'desc')
                ->first();
            // where('cash_date', '!=', $date)
            $opened_cashbok = Cashbook::where('cash_status', 'open')
                ->where('status', 'active')
                ->where('shop_id', $shop_id)
                ->orderBy('cash_date', 'desc')
                ->get();
            $all_cashbok = Cashbook::where('status', 'active')
                ->where('shop_id', $shop_id)
                ->orderBy('cash_date', 'desc')
                ->get();
            
            // order
            $order_progress = 0;
            $order_done = 0;
            $order_total = 0;
            
            // counting temporary
            if ($current_cashbook) 
            {
                // order
                $order_progress = Order::where('shop_id', $shop_id)
                    ->where('cashbook_id', $current_cashbook['id'])
                    ->where('status', '!=', 'done')
                    ->where('status', '!=', 'canceled')
                    ->count();
                $order_done = Order::where('shop_id', $shop_id)
                    ->where('cashbook_id', $current_cashbook['id'])
                    ->where('status', 'done')
                    ->count();
                $order_total = Order::where('shop_id', $shop_id)
                    ->where('cashbook_id', $current_cashbook['id'])
                    ->where('status', '!=', 'canceled')
                    ->count();
                $current_cashbook['order_progress'] = $order_progress;
                $current_cashbook['order_done'] = $order_done;
                $current_cashbook['order_total'] = $order_total;

                // counting temporary
                $cash_in = Order::where('shop_id', $shop_id)
                    ->where('cashbook_id', $current_cashbook['id'])
                    ->where('payment_status', true)
                    ->where('status', '!=', 'canceled')
                    ->sum('total_price');
                // $cash_out_order = Order::where('shop_id', $shop_id)
                //     ->where('cashbook_id', $current_cashbook['id'])
                //     ->where('payment_status', true)
                //     ->where('status', '!=', 'canceled')
                //     ->sum('change_price');
                $cash_expense = ExpenseList::where('shop_id', $shop_id)
                    ->where('cashbook_id', $current_cashbook['id'])
                    ->where('status', 'active')
                    ->sum('expense_price');
                // $cash_out = $cash_out_order + $cash_expense;
                $cash_out = $cash_expense;
                $cash_modal = $current_cashbook['cash_modal'];
                $cash_summary = ($cash_modal + $cash_in) - $cash_out;
                $cash_profit = $cash_summary - $cash_modal;
                $current_cashbook['cash_summary'] = (int)$cash_summary;
                $current_cashbook['cash_in'] = (int)$cash_in;
                $current_cashbook['cash_out'] = (int)$cash_out;
                $current_cashbook['cash_profit'] = (int)$cash_profit;
            }

            $payload = [
                'current_cashbook' => $current_cashbook,
                'opened_cashbook' => $opened_cashbok,
                'all_cashbook' => $all_cashbok,
                'shop' => $shop,
            ];

            $response = [
                'message' => 'proceed success',
                'status' => 'ok',
                'code' => '201',
                'data' => $payload
            ];
        }

        return response()->json($response, 200);
    }

    public function post(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'cashbook_id' => 'required|string|min:0|max:17|unique:cashbooks',
            'cash_date' => 'required|date|unique:cashbooks',
            'cash_modal' => 'required|integer',
            'cash_status' => 'required|string',
            'status' => 'required|string',
            'shop_id' => 'required|integer'
        ]);

        $response = [];

        if ($validator->fails()) 
        {
            $response = [
                'message' => $validator->errors(),
                'status' => 'invalide',
                'code' => '201',
                'data' => []
            ];
        } 
        else 
        {
            $payload = [
                'cashbook_id' => $req['cashbook_id'],
                'cash_date' => $req['cash_date'],
                'cash_end_date' => $req['cash_end_date'],
                'cash_modal' => $req['cash_modal'],
                'cash_summary' => $req['cash_summary'],
                'cash_actual' => $req['cash_actual'],
                'cash_profit' => $req['cash_profit'],
                'cash_in' => $req['cash_in'],
                'cash_out' => $req['cash_out'],
                'cash_status' => $req['cash_status'],
                'description' => $req['description'],
                'status' => $req['status'],
                'is_available' => $req['is_available'],
                'shop_id' => $req['shop_id'],
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = Cashbook::insert($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Cashbook::where(['cashbook_id' => $req['cashbook_id']])->first()
                ];
            }
            else 
            {
                $response = [
                    'message' => 'failed to save',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function update(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'cashbook_id' => 'required|string|min:0|max:17',
            'cash_date' => 'required|string',
            'cash_modal' => 'required|integer',
            'cash_status' => 'required|string',
            'status' => 'required|string',
            'shop_id' => 'required|integer'
        ]);

        $response = [];

        if ($validator->fails()) 
        {
            $response = [
                'message' => $validator->errors(),
                'status' => 'invalide',
                'code' => '201',
                'data' => []
            ];
        } 
        else 
        {
            $payload = [
                'cash_date' => $req['cash_date'],
                'cash_end_date' => $req['cash_end_date'],
                'cash_modal' => $req['cash_modal'],
                'cash_summary' => $req['cash_summary'],
                'cash_actual' => $req['cash_actual'],
                'cash_profit' => $req['cash_profit'],
                'cash_in' => $req['cash_in'],
                'cash_out' => $req['cash_out'],
                'cash_status' => $req['cash_status'],
                'description' => $req['description'],
                'status' => $req['status'],
                'is_available' => $req['is_available'],
                'shop_id' => $req['shop_id'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = Cashbook::where(['cashbook_id' => $req['cashbook_id']])->update($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Cashbook::where(['cashbook_id' => $req['cashbook_id']])->first()
                ];
            }
            else 
            {
                $response = [
                    'message' => 'failed to save',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function delete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'cashbook_id' => 'required|string|min:0|max:17',
        ]);

        $response = [];

        if ($validator->fails()) 
        {
            $response = [
                'message' => $validator->errors(),
                'status' => 'invalide',
                'code' => '201',
                'data' => []
            ];
        } 
        else 
        {
            $data = Cashbook::where(['cashbook_id' => $req['cashbook_id']])->delete();

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => []
                ];
            }
            else 
            {
                $response = [
                    'message' => 'failed to delete',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }
}
