<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Visitor;
use App\User;
use App\Table;
use App\Customer;

class VisitorController extends Controller
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
            'user_id' => 'integer',
            'owner_id' => 'integer'
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
            $oID = $req['owner_id'];
            $uID = $req['user_id'];
            $limit = $req['limit'];
            $offset = $req['offset'];

            if ($uID) 
            {
                $data = Visitor::where(['created_by' => $uID])->limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
            } 
            else 
            {
                if ($oID) {
                    $data = Visitor::where(['owner_id' => $oID])->limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
                } else {
                    $data = Visitor::limit($limit)->offset($offset)->orderBy('id', 'desc')->get();
                }
            }
            
            if ($data) 
            {
                $newPayload = array();

                $dump = json_decode($data, true);

                for ($i=0; $i < count($dump); $i++) { 
                    $visitor = $dump[$i];
                    $detailTable = Table::where(['id' => $visitor['table_id']])->first();
                    $detailUser = User::where(['id' => $visitor['user_id']])->first();
                    $detailCustomer = Customer::where(['id' => $visitor['owner_id']])->first();
                    $payload = [
                        'visitor' => $visitor,
                        'table' => $detailTable,
                        'user' => $detailUser,
                        'customer' => $detailCustomer
                    ];
                    array_push($newPayload, $payload);
                }

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

    public function getByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer'
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
            $id = $req['id'];
            $data = Visitor::where(['id' => $id])->first();
            
            if ($data) 
            {
                $detailTable = Table::where(['id' => $data['table_id']])->first();
                $detailUser = User::where(['id' => $data['user_id']])->first();
                $detailCustomer = Customer::where(['id' => $data['owner_id']])->first();
                $payload = [
                    'visitor' => $data,
                    'table' => $detailTable,
                    'user' => $detailUser,
                    'customer' => $detailCustomer
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

    public function post(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'status' => 'required|string',
            'owner_id' => 'required|integer',
            'table_id' => 'required|integer'
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
                'start_date' => $req['start_date'],
                'end_date' => $req['end_date'],
                'status' => $req['status'],
                'owner_id' => $req['owner_id'],
                'table_id' => $req['table_id'],
                'user_id' => Auth()->user()->id,
                'created_by' => Auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data = Visitor::insert($payload);

            if ($data)
            {
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
            'id' => 'required|integer',
            'status' => 'required|string',
            'owner_id' => 'required|integer',
            'table_id' => 'required|integer',
            'user_id' => 'required|integer'
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
                'start_date' => $req['start_date'],
                'end_date' => $req['end_date'],
                'status' => $req['status'],
                'owner_id' => $req['owner_id'],
                'table_id' => $req['table_id'],
                'user_id' => $req['user_id'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = Visitor::where(['id' => $req['id']])->update($payload);

            if ($data)
            {
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
            'id' => 'required|integer',
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
            $data = Visitor::where(['id' => $req['id']])->delete();

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
