<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\OrderItem;
use App\Order;
use App\Table;
use App\Customer;
use App\Address;
use App\Shipment;
use App\Payment;
use App\Shop;
use App\User;
use App\Employee;

class OrderItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getAll(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'limit' => 'required|integer',
            'offset' => 'required|integer',
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
            $shopID = $req['shop_id'];
            $limit = $req['limit'];
            $offset = $req['offset'];
            $totalRecord = 0;
            $status = $req['status'] ? ['status' => $req['status']] : [];
            $cashbookStatus = $req['cashbook_id'] ? ['cashbook_id' => $req['cashbook_id']] : [];
            $newStatus = array_merge($status, $cashbookStatus, ['shop_id' => $shopID]);
            $data = Order::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('order_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('customer_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('table_name', 'LIKE', '%'.$search.'%');
                })
                ->limit($limit)
                ->offset($offset)
                ->orderBy('id', 'desc')
                ->get();
            $totalRecord = Order::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('order_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('customer_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('table_name', 'LIKE', '%'.$search.'%');
                })
                ->count();
            
            if ($data) 
            {
                $newPayload = array();

                $limit = $req['limit'];
                $offset = $req['offset'];

                $dump = json_decode($data, true);

                for ($i=0; $i < count($dump); $i++) { 
                    $order = $dump[$i];
                    $table = Table::where(['id' => $dump[$i]['table_id']])->first();
                    $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();
                    
                    // order items 
                    $orderItems = array();
                    $details = OrderItem::where(['order_id' => $dump[$i]['id']])->orderBy('id', 'desc')->get();
                    
                    $dumpDetail = json_decode($details, true);

                    for ($j=0; $j < count($dumpDetail); $j++) { 
                        $detailPayload = $dumpDetail[$j];
                        
                        // USER
                        $detailUser = null;
                        if ($dumpDetail[$j]['assigned_id']) {
                            $detailUser = User::where('id', $dumpDetail[$j]['assigned_id'])->first();
                        }

                        // EMPLOYEE
                        $detailEmployee = null;
                        if ($detailUser && $detailUser['owner_id']) {
                            $detailEmployee = Employee::where('id', $detailUser['owner_id'])->first();
                        }

                        $detailPayload['user'] = $detailUser;
                        $detailPayload['employee'] = $detailEmployee;

                        array_push($orderItems, $detailPayload);
                    }

                    $payload = [
                        'order' => $order,
                        'details' => $orderItems,
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

    public function post(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'toping_price' => 'required|integer',
            'price' => 'required|integer',
            'quantity' => 'required|integer',
            'subtotal' => 'required|integer',
            'product_name' => 'required|string',
            // 'product_detail' => 'required|string',
            'order_id' => 'required|integer'
            // 'product_id' => 'required|integer',
            // 'proddetail_id' => 'required|integer'
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
                'toping_price' => $req['toping_price'],
                'price' => $req['price'],
                'quantity' => $req['quantity'],
                'subtotal' => $req['subtotal'],
                'product_name' => $req['product_name'],
                'product_detail' => $req['product_detail'],
                'product_toping' => $req['product_toping'],
                'order_id' => $req['order_id'],
                'product_id' => $req['product_id'],
                'proddetail_id' => $req['proddetail_id'],
                'toping_id' => $req['toping_id'],
                'shop_id' => $req['shop_id'],
                'assigned_id' => $req['assigned_id'],
                'status' => $req['status'],
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = OrderItem::insert($payload);

            if ($data)
            {
                // order items 
                $orderItems = array();
                $details = OrderItem::where(['order_id' => $req['order_id']])->orderBy('id', 'desc')->get();
                
                $dumpDetail = json_decode($details, true);

                for ($j=0; $j < count($dumpDetail); $j++) { 
                    $detailPayload = $dumpDetail[$j];
                    
                    // USER
                    $detailUser = null;
                    if ($dumpDetail[$j]['assigned_id']) {
                        $detailUser = User::where('id', $dumpDetail[$j]['assigned_id'])->first();
                    }

                    // EMPLOYEE
                    $detailEmployee = null;
                    if ($detailUser && $detailUser['owner_id']) {
                        $detailEmployee = Employee::where('id', $detailUser['owner_id'])->first();
                    }

                    $detailPayload['user'] = $detailUser;
                    $detailPayload['employee'] = $detailEmployee;

                    array_push($orderItems, $detailPayload);
                }

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $orderItems
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
            'id' => 'required|integer|min:0',
            'toping_price' => 'required|integer',
            'price' => 'required|integer',
            'quantity' => 'required|integer',
            'subtotal' => 'required|integer',
            'product_name' => 'required|string',
            // 'product_detail' => 'required|string',
            'order_id' => 'required|integer'
            // 'product_id' => 'required|integer',
            // 'proddetail_id' => 'required|integer'
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
                'toping_price' => $req['toping_price'],
                'price' => $req['price'],
                'quantity' => $req['quantity'],
                'subtotal' => $req['subtotal'],
                'product_name' => $req['product_name'],
                'product_detail' => $req['product_detail'],
                'product_toping' => $req['product_toping'],
                'order_id' => $req['order_id'],
                'product_id' => $req['product_id'],
                'proddetail_id' => $req['proddetail_id'],
                'toping_id' => $req['toping_id'],
                'shop_id' => $req['shop_id'],
                'assigned_id' => Auth()->user()->id,
                'status' => $req['status'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = OrderItem::where(['id' => $req['id']])->update($payload);

            if ($data)
            {
                // order items 
                $orderItems = array();
                $details = OrderItem::where(['order_id' => $req['order_id']])->orderBy('id', 'desc')->get();
                
                $dumpDetail = json_decode($details, true);

                for ($j=0; $j < count($dumpDetail); $j++) { 
                    $detailPayload = $dumpDetail[$j];
                    
                    // USER
                    $detailUser = null;
                    if ($dumpDetail[$j]['assigned_id']) {
                        $detailUser = User::where('id', $dumpDetail[$j]['assigned_id'])->first();
                    }

                    // EMPLOYEE
                    $detailEmployee = null;
                    if ($detailUser && $detailUser['owner_id']) {
                        $detailEmployee = Employee::where('id', $detailUser['owner_id'])->first();
                    }

                    $detailPayload['user'] = $detailUser;
                    $detailPayload['employee'] = $detailEmployee;

                    array_push($orderItems, $detailPayload);
                }

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $orderItems
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
            'id' => 'required|string|min:0|max:6',
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
            $data = OrderItem::where(['id' => $req['id']])->delete();

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
