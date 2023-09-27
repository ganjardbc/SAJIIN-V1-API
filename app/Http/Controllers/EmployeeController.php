<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Employee;
use App\Shop;
use App\Role;
use App\Shift;
use App\User;
use Image;

class EmployeeController extends Controller
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
            $data = Employee::where($newStt)
                ->where(function ($query) use ($search) {
                    $query->where('employee_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('name', 'LIKE', '%'.$search.'%')
                        ->orWhere('phone', 'LIKE', '%'.$search.'%')
                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                        ->orWhere('address', 'LIKE', '%'.$search.'%');
                })
                ->limit($limit)
                ->offset($offset)
                ->orderBy('id', 'desc')
                ->get();
            $totalRecord = Employee::where($newStt)
                ->where(function ($query) use ($search) {
                    $query->where('employee_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('name', 'LIKE', '%'.$search.'%')
                        ->orWhere('phone', 'LIKE', '%'.$search.'%')
                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                        ->orWhere('address', 'LIKE', '%'.$search.'%');
                })
                ->count();
            
            if ($data) 
            {
                $newPayload = array();

                $dump = json_decode($data, true);
                
                for ($i=0; $i < count($dump); $i++) { 
                    $employee = $dump[$i];
                    $shop = Shop::where('id', $employee['shop_id'])->first();
                    $role = Role::where('id', $employee['role_id'])->first();
                    $shift = Shift::where('id', $employee['shift_id'])->first();
                    $user = User::where('owner_id', $employee['id'])->first();
                    $payload = [
                        'employee' => $employee,
                        'shop' => $shop,
                        'role' => $role,
                        'shift' => $shift,
                        'user' => $user
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
            'employee_id' => 'required|string',
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
            $data = Employee::where(['employee_id' => $req['employee_id']])->first();
            
            if ($data) 
            {
                $payload = [
                    'employee' => $data,
                    'shop' => Shop::where('id', $data['shop_id'])->first(),
                    'role' => Role::where('id', $data['role_id'])->first(),
                    'shift' => Shift::where('id', $data['shift_id'])->first(),
                    'user' => User::where('owner_id', $data['id'])->first()
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

    public function removeImage(Request $req) 
    {
        $validator = Validator::make($req->all(), [
            'employee_id' => 'required|string|min:0|max:17',
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

            $filename = Employee::where(['employee_id' => $req['employee_id']])->first()->image;
            $data = Employee::where(['employee_id' => $req['employee_id']])->update($payload);

            if ($data)
            {
                unlink(public_path('contents/employees/thumbnails/'.$filename));
				unlink(public_path('contents/employees/covers/'.$filename));

                $response = [
                    'message' => 'proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => Employee::where(['employee_id' => $req['employee_id']])->first()
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
            'employee_id' => 'required|string|min:0|max:17',
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
            $id = $req['employee_id'];
            $image = $req['image'];

            $chrc = array('[',']','@',' ','+','-','#','*','<','>','_','(',')',';',',','&','%','$','!','`','~','=','{','}','/',':','?','"',"'",'^');
			$filename = $id.time().str_replace($chrc, '', $image->getClientOriginalName());
			$width = getimagesize($image)[0];
			$height = getimagesize($image)[1];

            //save image to server
			//creating thumbnail and save to server
			$destination = public_path('contents/employees/thumbnails/'.$filename);
			$img = Image::make($image->getRealPath());
			$thumbnail = $img->resize(400, 400, function ($constraint) {
					$constraint->aspectRatio();
				})->save($destination); 

			//saving image real to server
			$destination = public_path('contents/employees/covers/');
			$real = $image->move($destination, $filename);

            if ($thumbnail && $real) 
			{
                $payload = [
                    'image' => $filename,
                    'updated_by' => Auth()->user()->id,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
    
                $data = Employee::where(['employee_id' => $req['employee_id']])->update($payload);
    
                if ($data)
                {
                    $response = [
                        'message' => 'proceed success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => Employee::where(['employee_id' => $req['employee_id']])->first()
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
            'employee_id' => 'required|string|min:0|max:17|unique:employees',
            'name' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:13',
            'address' => 'required|string',
            'status' => 'string',
            'shop_id' => 'required|integer',
            'role_id' => 'required|integer',
            'shift_id' => 'required|integer',
            'username' => 'required|string|max:64|unique:users',
            'password' => 'required|string|min:6'
        ]);

        $response = [];
        $newPayload = null;

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
            // CREATE USER
            $payload = [
                'name' => $req['name'],
                'email' => $req['email'],
                'password' => Hash::make($req['password']),
                'username' => $req['username'],
                'status' => 'active',
                'enabled' => 1,
                'role_id' => $req['role_id']
            ];

            $user = User::insert($payload);

            if ($user) 
            {
                // CREATE EMPLOYEE
                $payload = [
                    'employee_id' => $req['employee_id'],
                    'name' => $req['name'],
                    'email' => $req['email'],
                    'phone' => $req['phone'],
                    'status' => $req['status'],
                    'about' => $req['about'],
                    'address' => $req['address'],
                    'shop_id' => $req['shop_id'],
                    'role_id' => $req['role_id'],
                    'shift_id' => $req['shift_id'],
                    'created_by' => Auth()->user()->id,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $employee = Employee::insert($payload);

                if ($employee) 
                {
                    // UPDATE USER
                    $employeeData = Employee::where(['employee_id' => $req['employee_id']])->first();

                    $payload = [
                        'owner_id' => $employeeData['id']
                    ];

                    $update = User::where(['username' => $req['username']])->update($payload);

                    if ($update)
                    {
                        $employeeUser = User::where(['username' => $req['username']])->first();

                        $newPayload['employee'] = $employeeData;
                        $newPayload['user'] = $employeeUser;

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
                            'message' => 'failed to update user and employee',
                            'status' => 'failed',
                            'code' => '201',
                            'data' => []
                        ];
                    }
                }
                else 
                {
                    $response = [
                        'message' => 'failed to create employee',
                        'status' => 'failed',
                        'code' => '201',
                        'data' => []
                    ];
                }
            }
            else 
            {
                $response = [
                    'message' => 'failed to create user',
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
            'employee_id' => 'required|string|min:0|max:17',
            'name' => 'required|string',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:13',
            'address' => 'required|string',
            'status' => 'string',
            'shop_id' => 'required|integer',
            'role_id' => 'required|integer',
            'shift_id' => 'required|integer',
            'user_id' => 'required|integer'
        ]);

        $response = [];
        $defaultMessage = null;
        $employeeData = null;
        $employeeUser = null;
        $newPayload = null;

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
            // CHECK IF USERNAME ALREADY USED
            if ($req['username'] !== $req['username_old']) {
                $checkUsername = User::where(['username'=> $req['username']])->first();

                if ($checkUsername) 
                {
                    $defaultMessage['username'] = ['The username has already been taken.'];
                    $response = [
                        'message' => $defaultMessage,
                        'status' => 'invalide',
                        'code' => '201',
                        'data' => []
                    ];
                }
            }

            // CHECK IF EMAIL ALREADY USED
            if ($req['email'] !== $req['email_old']) {
                $checkEmail = User::where(['email'=> $req['email']])->first();
                
                if ($checkEmail) 
                {
                    $defaultMessage['email'] = ['The email has already been taken.'];
                    $response = [
                        'message' => $defaultMessage,
                        'status' => 'invalide',
                        'code' => '201',
                        'data' => []
                    ];
                }
            }

            if (!$defaultMessage) {
                // UPDATE USER 
                $payload = [
                    'name' => $req['name'],
                    'email' => $req['email'],
                    'role_id' => $req['role_id']
                ];

                if ($req['password']) 
                {
                    $payload['password'] = Hash::make($req['password']);
                }

                if ($req['username']) 
                {
                    $payload['username'] = $req['username'];
                }

                $updateUser = User::where(['id' => $req['user_id']])->update($payload);

                if ($updateUser)
                {
                    $employeeUser = User::where(['email'=> $req['email']])->first();
                }

                // UPDATE EMPLOYEE 
                $payload = [
                    'name' => $req['name'],
                    'email' => $req['email'],
                    'phone' => $req['phone'],
                    'status' => $req['status'],
                    'about' => $req['about'],
                    'address' => $req['address'],
                    'shop_id' => $req['shop_id'],
                    'role_id' => $req['role_id'],
                    'shift_id' => $req['shift_id'],
                    'updated_by' => Auth()->user()->id,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
    
                $updateEmployee = Employee::where(['employee_id' => $req['employee_id']])->update($payload);

                if ($updateEmployee)
                {
                    $employeeData = Employee::where(['employee_id' => $req['employee_id']])->first();
                }

                if ($employeeUser && $employeeData) {
                    $newPayload['employee'] = $employeeData;
                    $newPayload['user'] = $employeeUser;

                    $response = [
                        'message' => 'proceed success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => $newPayload
                    ];
                } else {
                    $response = [
                        'message' => 'failed to save user and employee',
                        'status' => 'failed',
                        'code' => '201',
                        'data' => $newPayload
                    ];
                }
            }
        }

        return response()->json($response, 200);
    }

    public function delete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'employee_id' => 'required|string|min:0|max:17',
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
            $employee = Employee::where(['employee_id' => $req['employee_id']])->first();
            $deleteEmployee = Employee::where(['id' => $employee['id']])->delete();
            $deleteUser = User::where(['owner_id' => $employee['id']])->delete();

            if ($deleteEmployee && $deleteUser)
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
