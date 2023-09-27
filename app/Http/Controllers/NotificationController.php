<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notification;

class NotificationController extends Controller
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
            'status' => 'required|string',
            'shop_id' => 'required'
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
            $newStatus = array_merge(
                $status,
                ['shop_id' => $req['shop_id']]
            );
            $totalRecord = 0;
            $totalRead = 0;
            $totalUnread = 0;

            $data = Notification::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('notification_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('message', 'LIKE', '%'.$search.'%')
                        ->orWhere('type', 'LIKE', '%'.$search.'%');
                })
                ->limit($limit)
                ->offset($offset)
                ->orderBy('id', 'desc')
                ->get();
            $totalRecord = Notification::where($newStatus)
                ->where(function ($query) use ($search) {
                    $query->where('notification_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('message', 'LIKE', '%'.$search.'%')
                        ->orWhere('type', 'LIKE', '%'.$search.'%');
                })
                ->count();
            $totalRead = Notification::where($newStatus)->where('is_read', '1')->count();
            $totalUnread = Notification::where($newStatus)->where('is_read', '0')->count();
            
            if ($data) 
            {
                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $data,
                    'total_record' => $totalRecord,
                    'total_read' => $totalRead,
                    'total_unread' => $totalUnread
                ];
            } 
            else 
            {
                $response = [
                    'message' => 'failed to get datas',
                    'status' => 'failed',
                    'code' => '201',
                    'data' => [],
                    'total_record' => $totalRecord,
                    'total_read' => $totalRead,
                    'total_unread' => $totalUnread
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function getByID(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'notification_id' => 'required|string|min:0|max:17',
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
            $notification_id = $req['notification_id'];
            $data = Notification::where(['notification_id' => $notification_id])->first();
            
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

    public function getCount(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'limit' => 'required|integer',
            'offset' => 'required|integer',
            'status' => 'required|string',
            'shop_id' => 'required'
        ]);

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
            $response = [];

            $shop_id = $req['shop_id'];
            $data = Notification::GetCount($shop_id);

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
                    'data' => 0
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function post(Request $req)
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

    public function update(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'notification_id' => 'required|string|min:0',
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
                'message' => $req['message'],
                'target' => $req['target'],
                'type' => $req['type'],
                'status' => $req['status'],
                'is_read' => $req['is_read'],
                'shop_id' => $req['shop_id'],
                'updated_by' => Auth()->user()->id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data = Notification::where(['notification_id' => $req['notification_id']])->update($payload);

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

    public function delete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'notification_id' => 'required|string|min:0|max:17',
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
            $data = Notification::where(['notification_id' => $req['notification_id']])->delete();

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
