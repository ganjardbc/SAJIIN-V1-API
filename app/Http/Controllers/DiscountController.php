<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Discount;
use App\Shop;
use Image;

class DiscountController extends Controller
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
            $data = [];
            $totalRecord = 0;
            $search = $req['search'];
            $limit = $req['limit'];
            $offset = $req['offset'];
            $status = $req['status'] ? ['status' => $req['status']] : [];
            $shopStatus = $req['shop_id'] ? ['shop_id' => $req['shop_id']] : [];
            $discountTypeStatus = $req['discount_type'] ? ['discount_type' => $req['discount_type']] : [];
            $discountValueTypeStatus = $req['discount_value_type'] ? ['discount_value_type' => $req['discount_value_type']] : [];
            $newStatus = array_merge($status, $shopStatus, $discountTypeStatus, $discountValueTypeStatus);
            $data = Discount::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('discount_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('discount_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('discount_value', 'LIKE', '%'.$search.'%')
                        ->orWhere('discount_description', 'LIKE', '%'.$search.'%');
                })
                ->limit($limit)
                ->offset($offset)
                ->orderBy('id', 'desc')
                ->get();
            $totalRecord = Discount::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('discount_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('discount_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('discount_value', 'LIKE', '%'.$search.'%')
                        ->orWhere('discount_description', 'LIKE', '%'.$search.'%');
                })
                ->count();

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

    public function getByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'discount_id' => 'required|string|min:0|max:17',
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
            $discount_id = $req['discount_id'];
            $data = Discount::where(['discount_id' => $discount_id])->first();
            
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
            'discount_id' => 'required|string|min:0|max:17',
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
                'discount_image' => '',
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $filename = Discount::where(['discount_id' => $req['discount_id']])->first()->image;
            $data = Discount::where(['discount_id' => $req['discount_id']])->update($payload);

            if ($data)
            {
                unlink(public_path('contents/discounts/thumbnails/'.$filename));
				unlink(public_path('contents/discounts/covers/'.$filename));

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Discount::where(['discount_id' => $req['discount_id']])->first()
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
            'discount_id' => 'required|string|min:0|max:17',
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
            $id = $req['discount_id'];
            $image = $req['image'];

            $chrc = array('[',']','@',' ','+','-','#','*','<','>','_','(',')',';',',','&','%','$','!','`','~','=','{','}','/',':','?','"',"'",'^');
			$filename = $id.time().str_replace($chrc, '', $image->getClientOriginalName());
			$width = getimagesize($image)[0];
			$height = getimagesize($image)[1];

            //save image to server
			//creating thumbnail and save to server
			$destination = public_path('contents/discounts/thumbnails/'.$filename);
			$img = Image::make($image->getRealPath());
			$thumbnail = $img->resize(400, 400, function ($constraint) {
					$constraint->aspectRatio();
				})->save($destination); 

			//saving image real to server
			$destination = public_path('contents/discounts/covers/');
			$real = $image->move($destination, $filename);

            if ($thumbnail && $real) 
			{
                $payload = [
                    'discount_image' => $filename,
                    'updated_by' => Auth()->user()->id,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
    
                $data = Discount::where(['discount_id' => $req['discount_id']])->update($payload);
    
                if ($data)
                {
                    $response = [
                        'message' => 'proceed success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => Discount::where(['discount_id' => $req['discount_id']])->first()
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
            'discount_id' => 'required|string|min:0|unique:discounts',
            'discount_name' => 'required|string',
            'discount_type' => 'required|string',
            'discount_value_type' => 'required|string',
            'discount_value' => 'required|integer',
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
                'discount_id' => $req['discount_id'],
                'discount_name' => $req['discount_name'],
                'discount_description' => $req['discount_description'],
                'discount_type' => $req['discount_type'],
                'discount_value_type' => $req['discount_value_type'],
                'discount_value' => $req['discount_value'],
                'is_public' => false,
                'status' => $req['status'],
                'shop_id' => $req['shop_id'],
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = Discount::insert($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Discount::where(['discount_id' => $req['discount_id']])->first()
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
            'discount_id' => 'required|string|min:0',
            'discount_name' => 'required|string',
            'discount_type' => 'required|string',
            'discount_value_type' => 'required|string',
            'discount_value' => 'required|integer',
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
                'discount_name' => $req['discount_name'],
                'discount_description' => $req['discount_description'],
                'discount_type' => $req['discount_type'],
                'discount_value_type' => $req['discount_value_type'],
                'discount_value' => $req['discount_value'],
                'is_public' => false,
                'status' => $req['status'],
                'shop_id' => $req['shop_id'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = Discount::where(['discount_id' => $req['discount_id']])->update($payload);

            if ($data)
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Discount::where(['discount_id' => $req['discount_id']])->first()
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
            'discount_id' => 'required|string|min:0',
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
            $data = Discount::where(['discount_id' => $req['discount_id']])->delete();

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
