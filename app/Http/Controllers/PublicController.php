<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Customer;
use App\Address;
use App\Category;
use App\Product;
use App\ProductToping;
use App\ProductDetail;
use App\ProductImage;
use App\Benefit;
use App\Article;
use App\Shipment;
use App\Payment;
use App\Shop;
use App\Table;
use App\Notification;
use App\Order;
use App\OrderItem;
use App\Cashbook;

class PublicController extends Controller
{
    public function shopByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shop_id' => 'required|string|min:0|max:17',
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
            $date = $req['date'];
            $shop_id = $req['shop_id'];
            $data = Shop::where(['shop_id' => $shop_id])->first();
            
            if ($data) 
            {
                $cashbook = Cashbook::where('status', 'active')
                    ->where('shop_id', $data['id'])
                    ->orderBy('cash_date', 'desc')
                    ->first();
                $tables = Table::where(['shop_id' => $data['id']])->get();
                $newPayload = [
                    'shop' => $data,
                    'tables' => $tables,
                    'cashbook' => $cashbook
                ];

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $newPayload
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

    public function category(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'limit' => 'required|integer',
            'offset' => 'required|integer',
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
            $status = $req['status'];
            $limit = $req['limit'];
            $offset = $req['offset'];
            $stt = $status ? ['status' => $status] : [];
            $newStt = array_merge(
                $stt,
                ['shop_id' => $req['shop_id']]
            );
            $totalRecord = 0;

            $data = Category::where($newStt)->limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
            $totalRecord = Category::where($newStt)->count();

            if ($data) 
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $data,
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

    public function product(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'limit' => 'required|integer',
            'offset' => 'required|integer',
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
            $limit = $req['limit'];
            $offset = $req['offset'];
            $totalRecord = 0;

            $status = $req['status'] ? ['status' => $req['status']] : [];
            $category = $req['category'] ? ['category_id' => $req['category']] : [];
            $newStt = array_merge(
                $status, 
                $category, 
                ['shop_id' => $req['shop_id']]
            );
            $data = Product::where($newStt)->limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
            $totalRecord = Product::where($newStt)->count();
            
            if ($data) 
            {
                $newPayload = array();

                $dump = json_decode($data, true);

                for ($i=0; $i < count($dump); $i++) { 
                    $product = $dump[$i];
                    $detailStatus = ['status' => 'active'];
                    $detailProduct = ProductDetail::where(array_merge(['product_id' => $dump[$i]['id']], $detailStatus))->orderBy('id', 'desc')->get();
                    $detailImage = ProductImage::where(['product_id' => $dump[$i]['id']])->orderBy('id', 'desc')->get();
                    $detailToping = ProductToping::GetAll(1000, 0, $dump[$i]['id'], $detailStatus);
                    $categories = Category::get();
                    $payload = [
                        'product' => $product,
                        'details' => $detailProduct,
                        'images' => $detailImage,
                        'topings' => $detailToping,
                        'categories' => $categories
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

    public function productByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'product_id' => 'required|string',
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
            $product_id = $req['product_id'];
            $data = Product::where(['product_id' => $product_id])->first();
            
            if ($data) 
            {
                $dump = json_decode($data, true);
                $product = $dump;

                $detailStatus = ['status' => 'active'];
                $detailProduct = ProductDetail::where(array_merge(['product_id' => $dump['id']], $detailStatus))->orderBy('id', 'desc')->get();
                $detailImage = ProductImage::where(['product_id' => $dump['id']])->orderBy('id', 'desc')->get();
                $detailToping = ProductToping::GetAll(1000, 0, $dump['id'], []);
                $categories = Category::get();

                $newPayload = [
                    'product' => $product,
                    'details' => $detailProduct,
                    'images' => $detailImage,
                    'topings' => $detailToping,
                    'categories' => $categories
                ];

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $newPayload
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

    public function tables(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'limit' => 'required|integer',
            'offset' => 'required|integer',
            'shop_id' => 'integer'
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
            $sID = $req['shop_id'];
            $status = $req['status'];
            $limit = $req['limit'];
            $offset = $req['offset'];
            $stt = $status ? ['status' => $status] : [];
            $totalRecord = 0;

            if ($sID) {
                $data = Table::where($stt)
                    ->where(['shop_id' => $sID])
                    ->where('name', 'LIKE', '%'.$req['search'].'%')
                    ->limit($limit)
                    ->offset($offset)
                    ->orderBy('id', 'desc')
                    ->get();
                $totalRecord = Table::where($stt)
                    ->where(['shop_id' => $sID])
                    ->where('name', 'LIKE', '%'.$req['search'].'%')
                    ->count();
            }
            else 
            {
                $data = Table::where($stt)->limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
                $totalRecord = Table::where($stt)->count();
            }

            if ($data) 
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $data,
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

    public function payments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'limit' => 'required|integer',
            'offset' => 'required|integer',
            'status' => 'string',
            'shop_id' => 'integer'
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
            $sID = $req['shop_id'];
            $status = $req['status'];
            $limit = $req['limit'];
            $offset = $req['offset'];
            $stt = $status ? ['status' => $status] : [];
            $totalRecord = 0;

            if ($sID) {
                $shop = Shop::where('id', $sID)->first();
                $newStt = array_merge($stt, ['user_id' => $shop['user_id']]);
                $data = Payment::where($newStt)->limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
                $totalRecord = Payment::where($newStt)->count();
            } else {
                $newStt = array_merge($stt, ['user_id' => Auth()->user()->id]);
                $data = Payment::where($newStt)->limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
                $totalRecord = Payment::where($newStt)->count();
            }
            
            if ($data) 
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $data,
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

    public function createOrder(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order' => 'required',
            'details' => 'required',
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
            $payloadOrder = $req['order'];
            $payloadOrder['created_by'] = '';
            $payloadOrder['created_at'] = date('Y-m-d H:i:s');

            $cashbook = Cashbook::where('shop_id', $payloadOrder['shop_id'])
                ->where('cash_status', 'open')
                ->orderBy('cash_date', 'desc')
                ->first();
            if ($cashbook) 
            {
                $payloadOrder['cashbook_id'] = $cashbook['id'];
            }

            $order = Order::insert($payloadOrder);
            if ($order) 
            {
                $dataOrder = Order::where(['order_id' => $payloadOrder['order_id']])->first();

                if ($dataOrder->table_id) {
                    Table::where(['id' => $dataOrder->table_id])->update(['status' => 'inactive']);
                }

                $newPayloadItems = [];
                $payloadItems = $req['details'];

                $dump = $payloadItems;

                for ($i=0; $i < count($dump); $i++) { 
                    if ($dump[$i]['cart_id'] != null) {
                        $dump[$i]['order_id'] = $dataOrder['id'];
                        unset($dump[$i]['cart_id']);
                        unset($dump[$i]['disableButton']);
                        unset($dump[$i]['disableSelect']);
                        unset($dump[$i]['owner_id']);
                    }
                    array_push($newPayloadItems, $dump[$i]);
                }

                $item = OrderItem::insert($newPayloadItems);

                if ($item) 
                {
                    $dataItem = OrderItem::where(['order_id' => $dataOrder['id']])->get();

                    $req['order'] = $dataOrder;
                    $req['details'] = $dataItem;
                    
                    $payloadResponse = [
                        'order' => $req['order'],
                        'details' => $req['details'],
                        'customer' => $req['customer'],
                        'address' => $req['address'],
                        'shipment' => $req['shipment'],
                        'payment' => $req['payment'],
                        'config' => $req['config']
                    ];

                    $response = [
                        'message' => 'proceed success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => $payloadResponse
                    ];
                } 
                else 
                {
                    $response = [
                        'message' => 'failed to save order item',
                        'status' => 'failed',
                        'code' => '201',
                        'data' => []
                    ];
                }

                // SEND NOTIFICATION 
                $default = 'Kamu punya pesanan baru';
                $messageCustomer = '';
                $message = $default;
                
                if ($dataOrder['customer_name']) {
                    $messageCustomer = $default . ' dari ' . $dataOrder['customer_name'];
                    $message = $messageCustomer;
                }

                if ($dataOrder['table_name']) {
                    $message = $messageCustomer . ' di ' . $dataOrder['table_name'];
                }

                $payload = [
                    'notification_id' => 'NF-' . date_create()->getTimestamp(),
                    'message' => $message,
                    'target' => $dataOrder['order_id'],
                    'type' => 'order',
                    'status' => 'active',
                    'is_read' => 0,
                    'shop_id' => $dataOrder['shop_id'],
                    'created_by' => $dataOrder['shop_id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
    
                Notification::insert($payload);
            } 
            else 
            {
                $response = [
                    'message' => 'failed to save order',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function orderByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order_id' => 'required|string'
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
            $order_id = $req['order_id'];
            $data = Order::where(['order_id' => $order_id])->first();
            
            if ($data) 
            {
                $dump = json_decode($data, true);

                $order = $dump;
                $orderItems = OrderItem::where(['order_id' => $dump['id']])->orderBy('id', 'desc')->get();
                $table = Table::where(['id' => $dump['table_id']])->first();
                $customer = Customer::where(['id' => $dump['customer_id']])->first();
                $address = Address::where(['id' => $dump['address_id']])->first();
                $shipment = Shipment::where(['id' => $dump['shipment_id']])->first();
                $payment = Payment::where(['id' => $dump['payment_id']])->first();
                $shop = Shop::where(['id' => $dump['shop_id']])->first();

                // is there discount
                $is_discount = false;
                for ($index_is_discount=0; $index_is_discount < count($orderItems); $index_is_discount++) { 
                    if ($orderItems[$index_is_discount]['is_discount']) {
                        $is_discount = true;
                    }
                }

                // total full price
                $total_full_price = 0;
                for ($index_full_price=0; $index_full_price < count($orderItems); $index_full_price++) { 
                    if ($orderItems[$index_full_price]['is_discount']) {
                        $total_full_price += $orderItems[$index_full_price]['quantity'] * $orderItems[$index_full_price]['second_price'];
                    } else {
                        $total_full_price += $orderItems[$index_full_price]['quantity'] * $orderItems[$index_full_price]['price'];
                    }
                }

                // total discount 
                $total_discount = $total_full_price - $order['total_price'];

                $order['is_discount'] = $is_discount;
                $order['total_full_price'] = $total_full_price;
                $order['total_discount'] = $total_discount;

                $payload = [
                    'order' => $order,
                    'details' => $orderItems,
                    'table' => $table,
                    'customer' => $customer,
                    'address' => $address,
                    'shipment' => $shipment,
                    'payment' => $payment,
                    'shop' => $shop
                ];

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $payload
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

    public function sendNotif(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'notification_id' => 'required|string|min:0|unique:notifications',
            'message' => 'required|string',
            'target' => 'required|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'is_read' => 'required|boolean',
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
                'notification_id' => $req['notification_id'],
                'message' => $req['message'],
                'target' => $req['target'],
                'type' => $req['type'],
                'status' => $req['status'],
                'is_read' => $req['is_read'],
                'shop_id' => $req['shop_id'],
                'created_by' => $req['shop_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = Notification::insert($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Notification::where(['notification_id' => $req['notification_id']])->first()
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
}
