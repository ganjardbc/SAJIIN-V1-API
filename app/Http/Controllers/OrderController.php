<?php

namespace App\Http\Controllers;

use PDF;
use QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Cart;
use App\Order;
use App\OrderItem;
use App\PartnerConfiguration;
use App\Table;
use App\Customer;
use App\Address;
use App\Shipment;
use App\Payment;
use App\Shop;
use App\Cashbook;
use App\Platform;
use App\ExpenseList;
use App\ExpenseType;
use Carbon\Carbon;


class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum', [
            'except' => [
                'downloadReport',
                'downloadReceipt'
            ]
        ]);
    }

    public function getDashboard(Request $req)
    {
        $validator = Validator::make($req->all(), [
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
            $shID = $req['shop_id'];
            $data = Order::select([
                            DB::raw('DATE(created_at) AS date'),
                            DB::raw('COUNT(id) AS count')
                        ])
                        ->where(['shop_id' => $shID])
                        ->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
                        ->groupBy('date')
                        ->orderBy('date', 'ASC')
                        ->get();
            
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

    public function getReport(Request $req)
    {
        $validator = Validator::make($req->all(), [
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
            $orderListPayload = array();
            $expenseListPayload = array();
            $shopID = $req['shop_id'];
            $startDate = $req['start_date'];
            $endDate = $req['end_date'];
            $totalRecord = 0;
            $status = $req['status'] ? ['status' => $req['status']] : [];
            $paymentStatus = $req['payment_status'] == '0' || $req['payment_status'] == '1' ? ['payment_status' => $req['payment_status']] : [];
            $cashbookStatus = $req['cashbook_id'] ? ['cashbook_id' => $req['cashbook_id']] : [];
            $rangeDate = [Carbon::parse($startDate), Carbon::parse($endDate)];
            $newStatus = array_merge($status, $paymentStatus, $cashbookStatus, ['shop_id' => $shopID]);
            if ($req['cashbook_id']) 
            {
                $cashBook = Cashbook::where('id', $req['cashbook_id'])
                    ->where('status', 'active') 
                    ->first();
                
                // ORDER LSIT 
                $orderList = Order::where($newStatus)
                    ->where('status', '!=', 'canceled')
                    ->orderBy('id', 'desc')
                    ->get();
                $grandItem = Order::where($newStatus)
                    ->where('status', '!=', 'canceled')
                    ->sum('total_item');
                $grandTotal = Order::where($newStatus)
                    ->where('status', '!=', 'canceled')
                    ->sum('total_price');
                $grandBills = Order::where($newStatus)
                    ->where('status', '!=', 'canceled')
                    ->sum('bills_price');
                $grandChange = Order::where($newStatus)
                    ->where('status', '!=', 'canceled')
                    ->sum('change_price');
                $cashIn = Order::where($newStatus)
                    ->where('status', '!=', 'canceled')
                    ->sum('total_price');
                // $cashOutOrder = Order::where($newStatus)
                //     ->where('status', '!=', 'canceled')
                //     ->sum('change_price');
                
                // EXPENSE LIST 
                $expenseList = ExpenseList::where(array_merge($cashbookStatus, ['shop_id' => $shopID]))
                    ->where('status', 'active')
                    ->orderBy('id', 'desc')
                    ->get();
                $expenseListTotal = ExpenseList::where(array_merge($cashbookStatus, ['shop_id' => $shopID]))
                    ->where('status', 'active')
                    ->sum('expense_price');
                $expenseListItem = ExpenseList::where(array_merge($cashbookStatus, ['shop_id' => $shopID]))
                    ->where('status', 'active')
                    ->count('id');
                
                // COUNTING
                $cashModal = $cashBook['cash_modal'];
                $cashActual = $cashBook['cash_actual'];
                // $cashOut = $cashOutOrder + $expenseListTotal;
                $cashOut = $expenseListTotal;
                $cashSummary = ($cashModal + $cashIn) - $cashOut;
                $cashProfit = $cashSummary - $cashModal;
            }
            else 
            {
                $newStatus = array_merge($status, $paymentStatus, ['shop_id' => $shopID]);
                $newExpenseStatus = array_merge(['status' => 'active'], ['shop_id' => $shopID]);

                $cashBook = Cashbook::where('shop_id', $shopID)
                    ->where('status', 'active') 
                    ->where('cash_status', 'closed')
                    ->whereBetween('cash_date', $rangeDate)
                    ->orderBy('id', 'desc')
                    ->get();
                $cashBookIds = [];
                $cashBookJson = json_decode($cashBook, true);

                for ($i=0; $i < count($cashBookJson); $i++) { 
                    array_push($cashBookIds, $cashBookJson[$i]['id']);
                }

                // ORDER LIST
                $orderList = Order::where($newStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->orderBy('id', 'desc')
                    ->get();
                $grandItem = Order::where($newStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->sum('total_item');
                $grandTotal = Order::where($newStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->sum('total_price');
                $grandBills = Order::where($newStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->sum('bills_price');
                $grandChange = Order::where($newStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->sum('change_price');
                $cashIn = Order::where($newStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->sum('total_price');
                // $cashOutOrder = Order::where($newStatus)
                //     ->whereIn('cashbook_id', $cashBookIds)
                //     ->sum('change_price');
                
                // EXPENSE LIST
                $expenseList = ExpenseList::where($newExpenseStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->orderBy('id', 'desc')
                    ->get();
                $expenseListTotal = ExpenseList::where($newExpenseStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->sum('expense_price');
                $expenseListItem = ExpenseList::where($newExpenseStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->count('id');
                
                // COUNTING
                // $cashOut = $cashOutOrder + $expenseListTotal;
                $cashOut = $expenseListTotal;
                
                $cashModal = 0;
                for ($i=0; $i < count($cashBookJson); $i++) { 
                    $cashModal += $cashBookJson[$i]['cash_modal'];
                }

                $cashSummary = ($cashModal + $cashIn) - $cashOut;
                $cashProfit = $cashSummary - $cashModal;

                $cashActual = 0;
                for ($i=0; $i < count($cashBookJson); $i++) { 
                    $cashActual += $cashBookJson[$i]['cash_actual'];
                }
            }
            
            if ($orderList) 
            {
                $dump = json_decode($orderList, true);

                for ($i=0; $i < count($dump); $i++) { 
                    $order = $dump[$i];
                    $orderItems = OrderItem::where(['order_id' => $dump[$i]['id']])->orderBy('id', 'desc')->get();
                    $table = Table::where(['id' => $dump[$i]['table_id']])->first();
                    $customer = Customer::where(['id' => $dump[$i]['customer_id']])->first();
                    $address = Address::where(['id' => $dump[$i]['address_id']])->first();
                    $shipment = Shipment::where(['id' => $dump[$i]['shipment_id']])->first();
                    $payment = Payment::where(['id' => $dump[$i]['payment_id']])->first();
                    $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();

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

                    array_push($orderListPayload, $payload);
                }
            }

            if ($expenseList) 
            {
                $dump = json_decode($expenseList, true);

                for ($i=0; $i < count($dump); $i++) { 
                    $expense = $dump[$i];
                    $type = ExpenseType::where(['id' => $dump[$i]['expense_type_id']])->first();
                    $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();

                    $payload = [
                        'expense' => $expense,
                        'type' => $type,
                        'shop' => $shop
                    ];

                    array_push($expenseListPayload, $payload);
                }
            }

            $response = [
                'message' => 'proceed success',
                'status' => 'ok',
                'code' => '201',
                'order_list' => $orderListPayload,
                'total_record' => $totalRecord,
                'grand_item' => $grandItem,
                'grand_total' => $grandTotal,
                'grand_bills' => $grandBills,
                'grand_change' => $grandChange,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'cash_modal' => $cashModal,
                'cash_summary' => $cashSummary,
                'cash_profit' => $cashProfit,
                'cash_actual' => $cashActual,
                'expense_list' => $expenseListPayload,
                'expense_list_total' => $expenseListTotal,
                'expense_list_item' => $expenseListItem,
                'range_date' => $rangeDate,
                'cashBook' => $cashBook
            ];
        }

        return response()->json($response, 200);
    }

    public function downloadReceipt(Request $req)
    {
        $order_id = $req['order_id'];
        $size_x = $req['size_x'];
        $size_y = $req['size_y'];
        $response = [];
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
            $qrUrl = 'https://shop.sajiin-app-v1.my.id/visitor/'.$shop['shop_id'].'/order/'.$dump['order_id'];
            $qrcode = base64_encode(
                QrCode::format('svg')
                    ->size(120)
                    ->errorCorrection('L')
                    ->generate($qrUrl)
            );

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
                'shop' => $shop,
                'qrcode' => $qrcode
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
                'data' => $data
            ];
        }

        if ($size_x != '100%') {
            $mm = 3.55;
            $mm_x = 10 / $mm * $size_x;
            $mm_y = 10 / $mm * $size_y;
            $customPaper = array(0, 0, $mm_y, $mm_x);
            $pdf = PDF::loadview('reports.receipt', ['response' => $response])->setPaper($customPaper, 'landscape');
        } else {
            $pdf = PDF::loadview('reports.receipt', ['response' => $response]);
        }
        return $pdf->download('order-receipt');
    }

    public function downloadReport(Request $req)
    {
        $orderListPayload = array();
        $expenseListPayload = array();
        $shopID = $req['shop_id'];
        $startDate = $req['start_date'];
        $endDate = $req['end_date'];
        $totalRecord = 0;
        $status = $req['status'] ? ['status' => $req['status']] : [];
        $paymentStatus = $req['payment_status'] == '0' || $req['payment_status'] == '1' ? ['payment_status' => $req['payment_status']] : [];
        $cashbookStatus = $req['cashbook_id'] ? ['cashbook_id' => $req['cashbook_id']] : [];
        $rangeDate = [Carbon::parse($startDate), Carbon::parse($endDate)];
        $newStatus = array_merge($status, $paymentStatus, $cashbookStatus, ['shop_id' => $shopID]);

        if ($req['cashbook_id']) 
        {
            $cashBook = Cashbook::where('id', $req['cashbook_id'])
                ->where('status', 'active') 
                ->first();
            
            // ORDER LIST 
            $orderList = Order::where($newStatus)
                ->where('status', '!=', 'canceled')
                ->orderBy('id', 'desc')
                ->get();
            $grandItem = Order::where($newStatus)
                ->where('status', '!=', 'canceled')
                ->sum('total_item');
            $grandTotal = Order::where($newStatus)
                ->where('status', '!=', 'canceled')
                ->sum('total_price');
            $grandBills = Order::where($newStatus)
                ->where('status', '!=', 'canceled')
                ->sum('bills_price');
            $grandChange = Order::where($newStatus)
                ->where('status', '!=', 'canceled')
                ->sum('change_price');
            $cashIn = Order::where($newStatus)
                ->where('status', '!=', 'canceled')
                ->sum('total_price');
            // $cashOutOrder = Order::where($newStatus)
            //     ->where('status', '!=', 'canceled')
            //     ->sum('change_price');
            
            // EXPENSE LIST
            $expenseList = ExpenseList::where(array_merge($cashbookStatus, ['shop_id' => $shopID]))
                ->where('status', 'active')
                ->orderBy('id', 'desc')
                ->get();
            $expenseListTotal = ExpenseList::where(array_merge($cashbookStatus, ['shop_id' => $shopID]))
                ->where('status', 'active')
                ->sum('expense_price');
            $expenseListItem = ExpenseList::where(array_merge($cashbookStatus, ['shop_id' => $shopID]))
                ->where('status', 'active')
                ->count('id');
            
            // COUNTING 
            // $cashOut = $cashOutOrder + $expenseListTotal;
            $cashOut = $expenseListTotal;
            $cashModal = $cashBook['cash_modal'];
            $cashActual = $cashBook['cash_actual'];
            $cashSummary = ($cashModal + $cashIn) - $cashOut;
            $cashProfit = $cashSummary - $cashModal;
        }
        else 
        {
            $newStatus = array_merge($status, $paymentStatus, ['shop_id' => $shopID]);
            $newExpenseStatus = array_merge(['status' => 'active'], ['shop_id' => $shopID]);

            $cashBook = Cashbook::where('shop_id', $shopID)
                ->where('status', 'active') 
                ->where('cash_status', 'closed')
                ->whereBetween('cash_date', $rangeDate)
                ->orderBy('id', 'desc')
                ->get();
            $cashBookIds = [];
            $cashBookJson = json_decode($cashBook, true);

            for ($i=0; $i < count($cashBookJson); $i++) { 
                array_push($cashBookIds, $cashBookJson[$i]['id']);
            }

            // ORDER LIST
            $orderList = Order::where($newStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->orderBy('id', 'desc')
                ->get();
            $grandItem = Order::where($newStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->sum('total_item');
            $grandTotal = Order::where($newStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->sum('total_price');
            $grandBills = Order::where($newStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->sum('bills_price');
            $grandChange = Order::where($newStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->sum('change_price');
            $cashIn = Order::where($newStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->sum('total_price');
            // $cashOutOrder = Order::where($newStatus)
            //     ->whereIn('cashbook_id', $cashBookIds)
            //     ->sum('change_price');
            
            // EXPENSE LIST
            $expenseList = ExpenseList::where($newExpenseStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->orderBy('id', 'desc')
                ->get();
            $expenseListTotal = ExpenseList::where($newExpenseStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->sum('expense_price');
            $expenseListItem = ExpenseList::where($newExpenseStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->count('id');

            // COUNTING 
            // $cashOut = $cashOutOrder + $expenseListTotal;
            $cashOut = $expenseListTotal;
                
            $cashModal = 0;
            for ($i=0; $i < count($cashBookJson); $i++) { 
                $cashModal += $cashBookJson[$i]['cash_modal'];
            }

            $cashSummary = ($cashModal + $cashIn) - $cashOut;
            $cashProfit = $cashSummary - $cashModal;

            $cashActual = 0;
            for ($i=0; $i < count($cashBookJson); $i++) { 
                $cashActual += $cashBookJson[$i]['cash_actual'];
            }
        }

        if ($orderList) 
        {
            $dump = json_decode($orderList, true);

            for ($i=0; $i < count($dump); $i++) { 
                $order = $dump[$i];
                $orderItems = OrderItem::where(['order_id' => $dump[$i]['id']])->orderBy('id', 'desc')->get();
                $table = Table::where(['id' => $dump[$i]['table_id']])->first();
                $customer = Customer::where(['id' => $dump[$i]['customer_id']])->first();
                $address = Address::where(['id' => $dump[$i]['address_id']])->first();
                $shipment = Shipment::where(['id' => $dump[$i]['shipment_id']])->first();
                $payment = Payment::where(['id' => $dump[$i]['payment_id']])->first();
                $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();

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

                array_push($orderListPayload, $payload);
            }
        }

        if ($expenseList) 
        {
            $dump = json_decode($expenseList, true);

            for ($i=0; $i < count($dump); $i++) { 
                $expense = $dump[$i];
                $type = ExpenseType::where(['id' => $dump[$i]['expense_type_id']])->first();
                $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();

                $payload = [
                    'expense' => $expense,
                    'type' => $type,
                    'shop' => $shop
                ];

                array_push($expenseListPayload, $payload);
            }
        }

        $shop = Shop::where(['id' => $shopID])->first();

        $response = [
            'message' => 'proceed success',
            'status' => 'ok',
            'code' => '201',
            'order_list' => $orderListPayload,
            'total_record' => $totalRecord,
            'grand_item' => $grandItem,
            'grand_total' => $grandTotal,
            'grand_bills' => $grandBills,
            'grand_change' => $grandChange,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cash_modal' => $cashModal,
            'cash_summary' => $cashSummary,
            'cash_actual' => $cashActual,
            'cash_profit' => $cashProfit,
            'expense_list' => $expenseListPayload,
            'expense_list_total' => $expenseListTotal,
            'expense_list_item' => $expenseListItem,
            'shop' => $shop,
            'cashBook' => $cashBook,
            'range_date' => $rangeDate,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $pdf = PDF::loadview('reports.order', ['response' => $response]);
        return $pdf->download('order-reports');
    }

    public function getAll(Request $req)
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
            $search = $req['search'];
            $shopID = $req['shop_id'];
            $limit = $req['limit'];
            $offset = $req['offset'];
            $totalRecord = 0;
            $status = $req['status'] ? ['status' => $req['status']] : [['status', '!=', 'canceled']];
            $paymentStatus = $req['payment_status'] == '0' || $req['payment_status'] == '1' ? ['payment_status' => $req['payment_status']] : [];
            $cashbookStatus = $req['cashbook_id'] ? ['cashbook_id' => $req['cashbook_id']] : [];
            $newStatus = array_merge($status, $paymentStatus, $cashbookStatus, ['shop_id' => $shopID]);
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
                    $orderItems = OrderItem::where(['order_id' => $dump[$i]['id']])->orderBy('id', 'desc')->get();
                    $table = Table::where(['id' => $dump[$i]['table_id']])->first();
                    $customer = Customer::where(['id' => $dump[$i]['customer_id']])->first();
                    $address = Address::where(['id' => $dump[$i]['address_id']])->first();
                    $shipment = Shipment::where(['id' => $dump[$i]['shipment_id']])->first();
                    $payment = Payment::where(['id' => $dump[$i]['payment_id']])->first();
                    $cashBook = Cashbook::where(['id' => $dump[$i]['cashbook_id']])->first();
                    $platform = Platform::where(['id' => $dump[$i]['platform_id']])->first();
                    $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();
                    $cashier = User::where(['id' => $dump[$i]['created_by']])->first();

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
                        'cashbook' => $cashBook,
                        'platform' => $platform,
                        'shop' => $shop,
                        'cashier' => $cashier
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
                $cashBook = Cashbook::where(['id' => $dump['cashbook_id']])->first();
                $shop = Shop::where(['id' => $dump['shop_id']])->first();
                $cashier = User::where(['id' => $dump['created_by']])->first();

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
                    'cashBook' => $cashBook,
                    'shop' => $shop,
                    'cashier' => $cashier
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

    public function getCountByID(Request $req)
    {
        $response = [];

        $id = Auth()->user()->id;
        $data = Order::GetCountByID($id);
        
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

        return response()->json($response, 200);
    }

    public function getCountByStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shop_id' => 'required|integer'
        ]);

        $response = [];

        $shID = $req['shop_id'];
        $data = [];

        if ($shID) {
            $data = [
                'all_order' => Order::where(['shop_id' => $shID])->count(),
                'new_order' => Order::GetCountByShopStatusID($shID, 'new-order'),
                'on_progress' => Order::GetCountByShopStatusID($shID, 'on-progress'),
                'done' => Order::GetCountByShopStatusID($shID, 'done'),
                'canceled' => Order::GetCountByShopStatusID($shID, 'canceled')
            ];
        }
        
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

        return response()->json($response, 200);
    }

    public function getCountCustomerByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'owner_id' => 'required|integer'
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
            $id = $req['owner_id'];
            $data = [
                'all' => Order::GetCountCustomerByID($id),
                'allAdmin' => Order::GetCountCustomerAll($id),
                'unconfirmed' => Order::GetCountCustomerByStatusID($id, 'unconfirmed'),
                'confirmed' => Order::GetCountCustomerByStatusID($id, 'confirmed'),
                'cooking' => Order::GetCountCustomerByStatusID($id, 'cooking'),
                'packing' => Order::GetCountCustomerByStatusID($id, 'packing'),
                'shipping' => Order::GetCountCustomerByStatusID($id, 'shipping'),
                'done' => Order::GetCountCustomerByStatusID($id, 'done'),
                'canceled' => Order::GetCountCustomerByStatusID($id, 'canceled')
            ];
            
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

    public function postAdmin(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order' => 'required',
            'details' => 'required'
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
            $payloadOrder['created_by'] = Auth()->user()->id;
            $payloadOrder['created_at'] = date('Y-m-d H:i:s');

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
                    $dump[$i]['order_id'] = $dataOrder['id'];
                    array_push($newPayloadItems, $dump[$i]);
                }

                $item = OrderItem::insert($newPayloadItems);

                if ($item) 
                {
                    $dataItem = OrderItem::where(['order_id' => $dataOrder['id']])->get();

                    $req['order'] = $dataOrder;
                    $req['details'] = $dataItem;
                    $req['table'] = Table::where(['id' => $dataOrder['table_id']])->first();
                    $req['customer'] = Customer::where(['id' => $dataOrder['customer_id']])->first();
                    $req['address'] = Address::where(['id' => $dataOrder['address_id']])->first();
                    $req['shipment'] = Shipment::where(['id' => $dataOrder['shipment_id']])->first();
                    $req['payment'] = Payment::where(['id' => $dataOrder['payment_id']])->first();
                    $req['shop'] = Shop::where(['id' => $dataOrder['shop_id']])->first();
                    
                    $payloadResponse = [
                        'order' => $req['order'],
                        'details' => $req['details'],
                        'table' => $req['table'],
                        'customer' => $req['customer'],
                        'address' => $req['address'],
                        'shipment' => $req['shipment'],
                        'payment' => $req['payment'],
                        'shop' => $req['shop']
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

    public function postCustomer(Request $req)
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
            $payloadOrder['created_by'] = Auth()->user()->id;
            $payloadOrder['created_at'] = date('Y-m-d H:i:s');
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
                        Cart::where(['cart_id' => $dump[$i]['cart_id']])->delete();

                        $dump[$i]['order_id'] = $dataOrder['id'];
                        unset($dump[$i]['cart_id']);
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

    public function post(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order_id' => 'required|string|min:0|max:17|unique:orders',
            'delivery_fee' => 'integer|min:0',
            'total_price' => 'integer|min:0',
            'total_item' => 'integer|min:0',
            'payment_status' => 'required|boolean',
            'status' => 'required|string',
            'type' => 'required|string',
            'note' => 'max:255',
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
                'order_id' => $req['order_id'],
                'delivery_fee' => $req['delivery_fee'],
                'total_price' => $req['total_price'],
                'total_item' => $req['total_item'],
                'bills_price' => $req['bills_price'],
                'change_price' => $req['change_price'],
                'payment_status' => $req['payment_status'],
                'cashier_name' => $req['cashier_name'],
                'shop_name' => $req['shop_name'],
                'table_name' => $req['table_name'],
                'customer_name' => $req['customer_name'],
                'payment_name' => $req['payment_name'],
                'shipment_name' => $req['shipment_name'],
                'proof_of_payment' => $req['proof_of_payment'],
                'status' => $req['status'],
                'type' => $req['type'],
                'note' => $req['note'],
                'shop_id' => $req['shop_id'],
                'table_id' => $req['table_id'],
                'customer_id' => $req['customer_id'],
                'address_id' => $req['address_id'],
                'shipment_id' => $req['shipment_id'],
                'payment_id' => $req['payment_id'],
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = Order::insert($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Order::where(['order_id' => $req['order_id']])->first()
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

    public function postOrderStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order_id' => 'required|string|min:0|max:17',
            'status' => 'required|string',
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
                'status' => $req['status']
            ];

            $data = Order::where(['order_id' => $req['order_id']])->update($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Order::where(['order_id' => $req['order_id']])->first()
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

    public function postOrderPaymentStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order_id' => 'required|string|min:0|max:17',
            'payment_status' => 'required|boolean',
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
                'payment_status' => $req['payment_status']
            ];

            $data = Order::where(['order_id' => $req['order_id']])->update($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Order::where(['order_id' => $req['order_id']])->first()
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

    public function updateAdmin(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order' => 'required',
            'details' => 'required'
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
            $payloadOrder['updated_by'] = Auth()->user()->id;
            $payloadOrder['updated_at'] = date('Y-m-d H:i:s');
            
            unset($payloadOrder['is_discount']);
            unset($payloadOrder['total_full_price']);
            unset($payloadOrder['total_discount']);
            unset($payloadOrder['cashbook']);
            unset($payloadOrder['platform']);

            $order = Order::where(['id' => $payloadOrder['id']])->update($payloadOrder);
            if ($order) 
            {
                OrderItem::where(['order_id' => $payloadOrder['id']])->delete();

                $newPayloadItems = [];
                $payloadItems = $req['details'];

                $dump = $payloadItems;

                for ($i=0; $i < count($dump); $i++) { 
                    $dump[$i]['order_id'] = $payloadOrder['id'];
                    $dump[$i]['created_by'] = Auth()->user()->id;
                    $dump[$i]['created_at'] = date('Y-m-d H:i:s');
                    $dump[$i]['updated_by'] = Auth()->user()->id;
                    $dump[$i]['updated_at'] = date('Y-m-d H:i:s');
                    array_push($newPayloadItems, $dump[$i]);
                }

                $item = OrderItem::insert($newPayloadItems);

                if ($item) 
                {
                    $dataItem = OrderItem::where(['order_id' => $payloadOrder['id']])->get();

                    $req['order'] = $payloadOrder;
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

    public function update(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order_id' => 'required|string|min:0|max:17',
            'delivery_fee' => 'integer|min:0',
            'total_price' => 'integer|min:0',
            'total_item' => 'integer|min:0',
            'payment_status' => 'required|boolean',
            'status' => 'required|string',
            'type' => 'required|string',
            'note' => 'max:255'
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
            $dataOldOrder = Order::where(['order_id' => $req['order_id']])->first();

            $payload = [
                'delivery_fee' => $req['delivery_fee'],
                'total_price' => $req['total_price'],
                'total_item' => $req['total_item'],
                'bills_price' => $req['bills_price'],
                'change_price' => $req['change_price'],
                'payment_status' => $req['payment_status'],
                'cashier_name' => $req['cashier_name'],
                'shop_name' => $req['shop_name'],
                'table_name' => $req['table_name'],
                'customer_name' => $req['customer_name'],
                'payment_name' => $req['payment_name'],
                'shipment_name' => $req['shipment_name'],
                'proof_of_payment' => $req['proof_of_payment'],
                'status' => $req['status'],
                'type' => $req['type'],
                'note' => $req['note'],
                'shop_id' => $req['shop_id'],
                'table_id' => $req['table_id'],
                'customer_id' => $req['customer_id'],
                'address_id' => $req['address_id'],
                'shipment_id' => $req['shipment_id'],
                'payment_id' => $req['payment_id'],
                'cashbook_id' => $req['cashbook_id'],
                'platform_id' => $req['platform_id'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = Order::where(['order_id' => $req['order_id']])->update($payload);

            if ($data)
            {
                $dataNewOrder = Order::where(['order_id' => $req['order_id']])->first();

                // CHANGE FROM OLD TABLE NEW TABLE
                if ($dataOldOrder->table_id !== $dataNewOrder->table_id) 
                {
                    if ($dataOldOrder->table_id)
                    {
                        Table::where(['id' => $dataOldOrder->table_id])->update(['status' => 'active']);
                    }
                    if ($dataNewOrder->table_id)
                    {
                        Table::where(['id' => $dataNewOrder->table_id])->update(['status' => 'inactive']);
                    }
                }

                // CHANGE CURRENT TABLE STATUS
                if ($dataOldOrder->table_id === $dataNewOrder->table_id) 
                {
                    $statusTable = 'inactive';

                    if (
                        $dataNewOrder->status === 'done' || 
                        $dataNewOrder->status === 'canceled'
                    ) {
                        $statusTable = 'active';
                    }

                    Table::where(['id' => $dataNewOrder->table_id])->update(['status' => $statusTable]);
                }


                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $dataNewOrder
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
            'order_id' => 'required|string|min:0|max:17',
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
            $orderData = Order::where(['order_id' => $req['order_id']])->first();
            OrderItem::where(['order_id' => $orderData['id']])->delete();
            Order::where(['order_id' => $orderData['order_id']])->delete();

            $response = [
                'message' => 'proceed success',
                'status' => 'ok',
                'code' => '201',
                'data' => []
            ];
        }

        return response()->json($response, 200);
    }
}
