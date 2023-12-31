<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\ExpenseList;
use App\ExpenseType;
use App\Cashbook;
use App\Payment;
use App\Shop;
use Image;

class ExpenseListController extends Controller
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
            $filterCashbook = $req['cashbook_id'] ? ['cashbook_id' => $req['cashbook_id']] : [];
            $filterExpenseType = $req['expense_type_id'] ? ['expense_type_id' => $req['expense_type_id']] : [];
            $newStatus = array_merge(
                $status,
                $filterCashbook,
                $filterExpenseType,
                ['shop_id' => $req['shop_id']]
            );
            $totalRecord = 0;

            $data = ExpenseList::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('expense_list_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('expense_date', 'LIKE', '%'.$search.'%')
                        ->orWhere('expense_price', 'LIKE', '%'.$search.'%')
                        ->orWhere('description', 'LIKE', '%'.$search.'%');
                })
                ->limit($limit)
                ->offset($offset)
                ->orderBy('id', 'desc')
                ->get();
            $totalRecord = ExpenseList::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('expense_list_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('expense_date', 'LIKE', '%'.$search.'%')
                        ->orWhere('expense_price', 'LIKE', '%'.$search.'%')
                        ->orWhere('description', 'LIKE', '%'.$search.'%');
                })
                ->count();

            if ($data) 
            {
                $newPayload = array();

                $dump = json_decode($data, true);

                for ($i=0; $i < count($dump); $i++) { 
                    $expenseList = $dump[$i];
                    $expenseList['expense_type'] = ExpenseType::where(['id' => $dump[$i]['expense_type_id']])->first();
                    $expenseList['cashbook'] = Cashbook::where(['id' => $dump[$i]['cashbook_id']])->first();
                    $expenseList['payment'] = Payment::where(['id' => $dump[$i]['payment_id']])->first();
                    array_push($newPayload, $expenseList);
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
            'expense_list_id' => 'required|string|min:0|max:17',
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
            $expense_list_id = $req['expense_list_id'];
            $data = ExpenseList::where(['expense_list_id' => $expense_list_id])->first();
            
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

    public function removeImage(Request $req) 
    {
        $validator = Validator::make($req->all(), [
            'expense_list_id' => 'required|string|min:0|max:17',
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
                'image' => '',
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $filename = ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->first()->image;
            $data = ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->update($payload);

            if ($data)
            {
                unlink(public_path('contents/expense_lists/thumbnails/'.$filename));
				unlink(public_path('contents/expense_lists/covers/'.$filename));

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->first()
                ];
            }
            else 
            {
                $response = [
                    'message' => 'failed to remove image',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => []
                ];
            }
        }
    }

    public function uploadImage(Request $req) 
    {
        $validator = Validator::make($req->all(), [
            'expense_list_id' => 'required|string|min:0|max:17',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:1000000'
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
            $id = $req['expense_list_id'];
            $image = $req['image'];

            $chrc = array('[',']','@',' ','+','-','#','*','<','>','_','(',')',';',',','&','%','$','!','`','~','=','{','}','/',':','?','"',"'",'^');
			$filename = $id.time().str_replace($chrc, '', $image->getClientOriginalName());
			$width = getimagesize($image)[0];
			$height = getimagesize($image)[1];

            //save image to server
			//creating thumbnail and save to server
			$destination = public_path('contents/expense_lists/thumbnails/'.$filename);
			$img = Image::make($image->getRealPath());
			$thumbnail = $img->resize(400, 400, function ($constraint) {
					$constraint->aspectRatio();
				})->save($destination); 

			//saving image real to server
			$destination = public_path('contents/expense_lists/covers/');
			$real = $image->move($destination, $filename);

            if ($thumbnail && $real) 
			{
                $payload = [
                    'image' => $filename,
                    'updated_by' => Auth()->user()->id,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
    
                $data = ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->update($payload);
    
                if ($data)
                {
                    $response = [
                        'message' => 'proceed success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->first()
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
            else 
            {
                $response = [
                    'message' => 'failed to upload image',
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
            'expense_list_id' => 'required|string|min:0|max:17|unique:expense_lists',
            'expense_date' => 'required|string',
            'expense_price' => 'required|integer',
            'status' => 'required|string',
            'shop_id' => 'required',
            'cashbook_id' => 'required',
            'expense_type_id' => 'required',
            'payment_id' => 'required',
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
                'expense_list_id' => $req['expense_list_id'],
                'expense_date' => $req['expense_date'],
                'expense_price' => $req['expense_price'],
                'description' => $req['description'],
                'status' => $req['status'],
                'shop_id' => $req['shop_id'],
                'cashbook_id' => $req['cashbook_id'],
                'expense_type_id' => $req['expense_type_id'],
                'payment_id' => $req['payment_id'],
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = ExpenseList::insert($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->first()
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
            'expense_list_id' => 'required|string|min:0|max:17',
            'expense_date' => 'required|string',
            'expense_price' => 'required|integer',
            'status' => 'required|string',
            'shop_id' => 'required',
            'cashbook_id' => 'required',
            'expense_type_id' => 'required',
            'payment_id' => 'required',
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
                'expense_date' => $req['expense_date'],
                'expense_price' => $req['expense_price'],
                'description' => $req['description'],
                'status' => $req['status'],
                'shop_id' => $req['shop_id'],
                'cashbook_id' => $req['cashbook_id'],
                'expense_type_id' => $req['expense_type_id'],
                'payment_id' => $req['payment_id'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->update($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->first()
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
            'expense_list_id' => 'required|string|min:0|max:17',
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
            $data = ExpenseList::where(['expense_list_id' => $req['expense_list_id']])->delete();

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
