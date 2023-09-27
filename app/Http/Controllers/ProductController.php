<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Product;
use App\ProductDetail;
use App\ProductImage;
use App\Category;
use App\ProductToping;
use App\Shop;
use Image;

class ProductController extends Controller
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
            'shop_id' => 'required|integer',
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
            $totalRecord = 0;

            $status = $req['status'] ? ['status' => $req['status']] : [];
            $category = $req['category'] ? ['category_id' => $req['category']] : [];
            $newStt = array_merge(
                $status, 
                $category, 
                ['shop_id' => $req['shop_id']]
            );
            $data = Product::where($newStt)
                ->where(function ($query) use ($search) {
                    $query->where('product_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('name', 'LIKE', '%'.$search.'%')
                        ->orWhere('description', 'LIKE', '%'.$search.'%');
                })
                ->limit($limit)
                ->offset($offset)
                ->orderBy('id', 'desc')
                ->get();
            $totalRecord = Product::where($newStt)
                ->where(function ($query) use ($search) {
                    $query->where('product_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('name', 'LIKE', '%'.$search.'%')
                        ->orWhere('description', 'LIKE', '%'.$search.'%');
                })
                ->count();
            
            if ($data) 
            {
                $newPayload = array();

                $dump = json_decode($data, true);

                for ($i=0; $i < count($dump); $i++) { 
                    $product = $dump[$i];
                    $stt = $status ? ['status' => $status] : [];
                    $detailProduct = ProductDetail::where(array_merge(['product_id' => $dump[$i]['id']], $stt))->get();
                    $detailImage = ProductImage::where(['product_id' => $dump[$i]['id']])->get();
                    $payload = [
                        'product' => $product,
                        'details' => $detailProduct,
                        'images' => $detailImage
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
            $data = Product::GetByID($product_id);
            
            if ($data) 
            {
                $dump = json_decode($data, true);
                $product = $dump;
                $detailProduct = ProductDetail::where(['product_id' => $dump['id']])->get();
                $detailImage = ProductImage::where(['product_id' => $dump['id']])->get();

                $newPayload = [
                    'product' => $product,
                    'details' => $detailProduct,
                    'images' => $detailImage
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

    public function removeImage(Request $req) 
    {
        $validator = Validator::make($req->all(), [
            'product_id' => 'required|string|min:0|max:17',
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

            $filename = Product::where(['product_id' => $req['product_id']])->first()->image;
            $data = Product::where(['product_id' => $req['product_id']])->update($payload);

            if ($data)
            {
                unlink(public_path('contents/products/thumbnails/'.$filename));
				unlink(public_path('contents/products/covers/'.$filename));

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Product::where(['product_id' => $req['product_id']])->first()
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
            'product_id' => 'required|string|min:0|max:17',
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
            $id = $req['product_id'];
            $image = $req['image'];

            $chrc = array('[',']','@',' ','+','-','#','*','<','>','_','(',')',';',',','&','%','$','!','`','~','=','{','}','/',':','?','"',"'",'^');
			$filename = $id.time().str_replace($chrc, '', $image->getClientOriginalName());
			$width = getimagesize($image)[0];
			$height = getimagesize($image)[1];

            //save image to server
			//creating thumbnail and save to server
			$destination = public_path('contents/products/thumbnails/'.$filename);
			$img = Image::make($image->getRealPath());
			$thumbnail = $img->resize(400, 400, function ($constraint) {
					$constraint->aspectRatio();
				})->save($destination); 

			//saving image real to server
			$destination = public_path('contents/products/covers/');
			$real = $image->move($destination, $filename);

            if ($thumbnail && $real) 
			{
                $payload = [
                    'image' => $filename,
                    'updated_by' => Auth()->user()->id,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
    
                $data = Product::where(['product_id' => $req['product_id']])->update($payload);
    
                if ($data)
                {
                    $response = [
                        'message' => 'proceed success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => Product::where(['product_id' => $req['product_id']])->first()
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
            'product_id' => 'required|string|min:0|max:17|unique:products',
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|integer',
            'is_pinned' => 'required|boolean',
            'is_available' => 'required|boolean',
            'status' => 'required|string',
            'category_id' => 'required|integer',
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
                'product_id' => $req['product_id'],
                'name' => $req['name'],
                'image' => $req['image'],
                'description' => $req['description'],
                'note' => $req['note'],
                'type' => $req['type'],
                'price' => $req['price'],
                'is_pinned' => $req['is_pinned'],
                'is_available' => $req['is_available'],
                'status' => $req['status'],
                'category_id' => $req['category_id'],
                'shop_id' => $req['shop_id'],
                'user_id' => Auth()->user()->id,
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = Product::insert($payload);

            if ($data)
            {
                $dataProduct = Product::where(['product_id' => $req['product_id']])->first();
                $newPayloadItems = [];
                $payloadItems = $req['details'];

                $dump = $payloadItems;

                for ($i=0; $i < count($dump); $i++) { 
                    $dump[$i]['product_id'] = $dataProduct['id'];
                    $dump[$i]['created_by'] = Auth()->user()->id;
                    $dump[$i]['created_at'] = date('Y-m-d H:i:s');
                    array_push($newPayloadItems, $dump[$i]);
                }

                $item = ProductDetail::insert($newPayloadItems);

                $resultPayload['product'] = $dataProduct;

                if ($item) {
                    $resultPayload['details'] = ProductDetail::where(['product_id' => $dataProduct['id']])->get();
                }

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $resultPayload
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
            'product_id' => 'required|string|min:0|max:17',
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|integer',
            'is_pinned' => 'required|boolean',
            'is_available' => 'required|boolean',
            'status' => 'required|string',
            'category_id' => 'required|integer',
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
                'name' => $req['name'],
                'image' => $req['image'],
                'description' => $req['description'],
                'note' => $req['note'],
                'type' => $req['type'],
                'price' => $req['price'],
                'is_pinned' => $req['is_pinned'],
                'is_available' => $req['is_available'],
                'status' => $req['status'],
                'category_id' => $req['category_id'],
                'shop_id' => $req['shop_id'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = Product::where(['product_id' => $req['product_id']])->update($payload);

            if ($data)
            {
                $dataProduct = Product::where(['product_id' => $req['product_id']])->first();
                $newPayloadItems = [];
                $payloadItems = $req['details'];

                $dump = $payloadItems;

                for ($i=0; $i < count($dump); $i++) { 
                    $dump[$i]['product_id'] = $dataProduct['id'];
                    $dump[$i]['created_by'] = Auth()->user()->id;
                    $dump[$i]['created_at'] = date('Y-m-d H:i:s');
                    $dump[$i]['updated_by'] = Auth()->user()->id;
                    $dump[$i]['updated_at'] = date('Y-m-d H:i:s');
                    unset($dump[$i]['id']);
                    array_push($newPayloadItems, $dump[$i]);
                }

                ProductDetail::where(['product_id' => $dataProduct['id']])->delete();

                $item = ProductDetail::insert($newPayloadItems);

                $resultPayload['product'] = $dataProduct;

                if ($item) {
                    $resultPayload['details'] = ProductDetail::where(['product_id' => $dataProduct['id']])->get();
                }

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $resultPayload
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
            'product_id' => 'required|string|min:0|max:17',
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
            $data = Product::where(['product_id' => $req['product_id']])->first();

            if ($data)
            {
                ProductDetail::where(['product_id' => $data->id])->delete();
                ProductImage::where(['product_id' => $data->id])->delete();
                ProductToping::where(['product_id' => $data->id])->delete();
                Product::where(['id' => $data->id])->delete();

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
