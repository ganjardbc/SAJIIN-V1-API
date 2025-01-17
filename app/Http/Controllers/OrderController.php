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
use App\Notification;
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
                $cashBook = Cashbook::where('id', $req['cashbook_id'])->first();
                
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
                $cashOutOrder = Order::where($newStatus)
                    ->where('status', '!=', 'canceled')
                    ->sum('change_price');
                
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
                $cashOut = $cashOutOrder + $expenseListTotal;
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
                $cashOutOrder = Order::where($newStatus)
                    ->whereIn('cashbook_id', $cashBookIds)
                    ->sum('change_price');
                
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
                $cashOut = $cashOutOrder + $expenseListTotal;
                
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
                    $payment = Payment::where(['id' => $dump[$i]['payment_id']])->first();
                    $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();

                    $payload = [
                        'expense' => $expense,
                        'type' => $type,
                        'payment' => $payment,
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
            $qrUrl = 'https://shop.sajiin.com/visitor/'.$shop['shop_id'].'/order/'.$dump['order_id'];
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
            $cashOutOrder = Order::where($newStatus)
                ->where('status', '!=', 'canceled')
                ->sum('change_price');
            
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
            $cashOut = $cashOutOrder + $expenseListTotal;
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
            $cashOutOrder = Order::where($newStatus)
                ->whereIn('cashbook_id', $cashBookIds)
                ->sum('change_price');
            
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
            $cashOut = $cashOutOrder + $expenseListTotal;
                
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
                $payment = Payment::where(['id' => $dump[$i]['payment_id']])->first();
                $shop = Shop::where(['id' => $dump[$i]['shop_id']])->first();

                $payload = [
                    'expense' => $expense,
                    'type' => $type,
                    'payment' => $payment,
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
                ->orderBy('updated_at', 'desc')
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
                'ready' => Order::GetCountByShopStatusID($shID, 'ready'),
                'delivered' => Order::GetCountByShopStatusID($shID, 'delivered'),
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
            $payloadOrder = [
                'order_id' => $req['order']['order_id'],
                'note' => $req['order']['note'],
                'status' => $req['order']['status'],
                'type' => $req['order']['type'],
                'total_item' => $req['order']['total_item'],
                'total_price' => $req['order']['total_price'],
                'bills_price' => $req['order']['bills_price'],
                'cashbook_id' => $req['order']['cashbook_id'],
                'cashier_name' => $req['order']['cashier_name'],
                'change_price' => $req['order']['change_price'],
                'customer_id' => $req['order']['customer_id'],
                'customer_name' => $req['order']['customer_name'],
                'payment_id' => $req['order']['payment_id'],
                'payment_name' => $req['order']['payment_name'],
                'payment_status' => $req['order']['payment_status'],
                'platform_id' => $req['order']['platform_id'],
                'platform_price' => $req['order']['platform_price'],
                'platform_fee' => $req['order']['platform_fee'],
                'platform_image' => $req['order']['platform_image'],
                'platform_name' => $req['order']['platform_name'],
                'platform_type' => $req['order']['platform_type'],
                'platform_currency_type' => $req['order']['platform_currency_type'],
                'is_platform' => $req['order']['is_platform'],
                'discount_id' => $req['order']['discount_id'],
                'discount_image' => $req['order']['discount_image'],
                'discount_name' => $req['order']['discount_name'],
                'discount_description' => $req['order']['discount_description'],
                'discount_price' => $req['order']['discount_price'],
                'discount_fee' => $req['order']['discount_fee'],
                'discount_type' => $req['order']['discount_type'],
                'discount_value' => $req['order']['discount_value'],
                'discount_value_type' => $req['order']['discount_value_type'],
                'is_discount' => $req['order']['is_discount'],
                'shop_id' => $req['order']['shop_id'],
                'shop_name' => $req['order']['shop_name'],
                'table_id' => $req['order']['table_id'],
                'table_name' => $req['order']['table_name'],
                'proof_of_payment' => $req['order']['proof_of_payment'],
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $order = Order::insert($payloadOrder);
            if ($order) 
            {
                $dataOrder = Order::where(['order_id' => $payloadOrder['order_id']])->first();

                if ($dataOrder['table_id']) {
                    Table::where(['id' => $dataOrder['table_id']])->update(['status' => 'inactive']);
                }

                $payloadItems = $req['details'];
                $payloadOrderItems = [];
                for ($i=0; $i < count($payloadItems); $i++) { 
                    $items = $payloadItems[$i];
                    $payload = [
                        'note' => $items['note'],
                        'price' => $items['price'],
                        'second_price' => $items['second_price'],
                        'quantity' => $items['quantity'],
                        'subtotal' => $items['subtotal'],
                        'discount' => $items['discount'],
                        'discount_id' => $items['discount_id'],
                        'discount_image' => $items['discount_image'],
                        'discount_name' => $items['discount_name'],
                        'discount_description' => $items['discount_description'],
                        'discount_price' => $items['discount_price'],
                        'discount_fee' => $items['discount_fee'],
                        'discount_type' => $items['discount_type'],
                        'discount_value' => $items['discount_value'],
                        'discount_value_type' => $items['discount_value_type'],
                        'is_discount' => $items['is_discount'],
                        'platform' => $items['platform'],
                        'platform_id' => $items['platform_id'],
                        'platform_price' => $items['platform_price'],
                        'platform_fee' => $items['platform_fee'],
                        'platform_image' => $items['platform_image'],
                        'platform_name' => $items['platform_name'],
                        'platform_type' => $items['platform_type'],
                        'platform_currency_type' => $items['platform_currency_type'],
                        'is_platform' => $items['is_platform'],
                        'proddetail_id' => $items['proddetail_id'],
                        'product_detail' => $items['product_detail'],
                        'product_id' => $items['product_id'],
                        'product_image' => $items['product_image'],
                        'product_name' => $items['product_name'],
                        'status' => $items['status'],
                        'assigned_id' => $items['assigned_id'],
                        'shop_id' => $items['shop_id'],
                        'order_id' => $dataOrder['id'],
                        'created_by' => Auth()->user()->id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_by' => Auth()->user()->id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    array_push($payloadOrderItems, $payload);
                }

                $item = OrderItem::insert($payloadOrderItems);

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
                'created_at' => date('Y-m-d H:i:s'),
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
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
                    $items = $dump[$i];
                    $payload = [
                        'note' => $items['note'],
                        'price' => $items['price'],
                        'second_price' => $items['second_price'],
                        'quantity' => $items['quantity'],
                        'subtotal' => $items['subtotal'],
                        'discount' => $items['discount'],
                        'discount_id' => $items['discount_id'],
                        'discount_image' => $items['discount_image'],
                        'discount_name' => $items['discount_name'],
                        'discount_description' => $items['discount_description'],
                        'discount_price' => $items['discount_price'],
                        'discount_fee' => $items['discount_fee'],
                        'discount_type' => $items['discount_type'],
                        'discount_value' => $items['discount_value'],
                        'discount_value_type' => $items['discount_value_type'],
                        'is_discount' => $items['is_discount'],
                        'platform' => $items['platform'],
                        'platform_id' => $items['platform_id'],
                        'platform_price' => $items['platform_price'],
                        'platform_fee' => $items['platform_fee'],
                        'platform_image' => $items['platform_image'],
                        'platform_name' => $items['platform_name'],
                        'platform_type' => $items['platform_type'],
                        'platform_currency_type' => $items['platform_currency_type'],
                        'is_platform' => $items['is_platform'],
                        'proddetail_id' => $items['proddetail_id'],
                        'product_detail' => $items['product_detail'],
                        'product_id' => $items['product_id'],
                        'product_image' => $items['product_image'],
                        'product_name' => $items['product_name'],
                        'status' => $items['status'],
                        'assigned_id' => $items['assigned_id'],
                        'shop_id' => $items['shop_id'],
                        'order_id' => $payloadOrder['id'],
                        'created_by' => Auth()->user()->id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_by' => Auth()->user()->id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    array_push($newPayloadItems, $payload);
                }

                $item = OrderItem::insert($newPayloadItems);

                if ($item) 
                {
                    $dataItem = OrderItem::where(['order_id' => $payloadOrder['id']])->get();

                    $req['order'] = $payloadOrder;
                    $req['details'] = $newPayloadItems;
                    
                    $payloadResponse = [
                        'order' => $req['order'],
                        'details' => $req['details']
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
                'status' => $req['status'],
                'type' => $req['type'],
                'note' => $req['note'],
                'total_price' => $req['total_price'],
                'total_item' => $req['total_item'],
                'bills_price' => $req['bills_price'],
                'change_price' => $req['change_price'],
                'payment_id' => $req['payment_id'],
                'payment_name' => $req['payment_name'],
                'payment_status' => $req['payment_status'],
                'cashbook_id' => $req['cashbook_id'],
                'cashier_name' => $req['cashier_name'],
                'platform_id' => $req['platform_id'],
                'platform_price' => $req['platform_price'],
                'platform_fee' => $req['platform_fee'],
                'platform_image' => $req['platform_image'],
                'platform_name' => $req['platform_name'],
                'platform_type' => $req['platform_type'],
                'platform_currency_type' => $req['platform_currency_type'],
                'is_platform' => $req['is_platform'],
                'discount_id' => $req['discount_id'],
                'discount_image' => $req['discount_image'],
                'discount_name' => $req['discount_name'],
                'discount_description' => $req['discount_description'],
                'discount_price' => $req['discount_price'],
                'discount_fee' => $req['discount_fee'],
                'discount_type' => $req['discount_type'],
                'discount_value' => $req['discount_value'],
                'discount_value_type' => $req['discount_value_type'],
                'is_discount' => $req['is_discount'],
                'shop_id' => $req['shop_id'],
                'shop_name' => $req['shop_name'],
                'table_id' => $req['table_id'],
                'table_name' => $req['table_name'],
                'customer_id' => $req['customer_id'],
                'customer_name' => $req['customer_name'],
                'shipment_id' => $req['shipment_id'],
                'shipment_name' => $req['shipment_name'],
                'delivery_fee' => $req['delivery_fee'],
                'proof_of_payment' => $req['proof_of_payment'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = Order::where(['order_id' => $req['order_id']])->update($payload);

            if ($data)
            {
                $dataNewOrder = Order::where(['order_id' => $req['order_id']])->first();

                // UPDATE ORDER ITEM WHEN STATUS READY
                if ($dataNewOrder->status === 'ready') {
                    OrderItem::where('order_id', $dataNewOrder->id)->update(['status' => 'done']);
                }

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

                // SEND NOTIFICATION
                $default = 'Pesanan';
                $orderStatus = ' berubah';
                if ($dataNewOrder['status'] === 'new-order') {
                    $orderStatus = ' diterima toko';
                }
                if ($dataNewOrder['status'] === 'on-progress') {
                    $orderStatus = ' sedang disiapkan';
                }
                if ($dataNewOrder['status'] === 'ready') {
                    $orderStatus = ' sedang diantarkan';
                }
                if ($dataNewOrder['status'] === 'delivered') {
                    $orderStatus = ' diterima pelanggan';
                }
                if ($dataNewOrder['status'] === 'done') {
                    $orderStatus = ' sudah selesai';
                }
                if ($dataNewOrder['status'] === 'canceled') {
                    $orderStatus = ' dibatalkan';
                }
                if ($dataNewOrder['customer_name']) {
                    $messageCustomer = $default . ' atas nama ' . $dataNewOrder['customer_name'];
                    $message = $messageCustomer . $orderStatus;
                } else {
                    $message = $default . $dataNewOrder['order_id'] . $orderStatus;
                }

                $payload = [
                    'notification_id' => 'NF-' . date_create()->getTimestamp(),
                    'message' => $message,
                    'target' => $dataNewOrder['order_id'],
                    'type' => 'order-status',
                    'status' => 'active',
                    'is_read' => 0,
                    'shop_id' => $dataNewOrder['shop_id'],
                    'created_by' => $dataNewOrder['shop_id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
    
                Notification::insert($payload);

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
