<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\RolePermission;
use App\Role;
use App\Shop;
use App\Employee;
use App\Position;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum', [
        	'except' => [
        		'login', 
                'loginUsername',
        		'register'
        	]
        ]);
    }

    public function checkUsername(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'username' => 'required|string|max:64|unique:users',
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
            $user = User::where(['username'=> $req['username']])->first();

            if ($user) 
            {
                $response = [
                    'message' => 'Proceed success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => $user
                ];
            }
            else 
            {
                $response = [
                    'message' => 'Username is incorrect',
                    'status' => 'username-invalid',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function checkEmail(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required|string|max:255|unique:users',
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
            $user = User::where(['email'=> $req['email']])->first();

            if ($user) 
            {
                $response = [
                    'message' => [
                        'email' => ['The email has already been taken.']
                    ],
                    'status' => 'invalide',
                    'code' => '201',
                    'data' => []
                ];
            }
            else 
            {
                $response = [
                    'message' => '',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function login(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6'
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
            $credentials = $req->only('email', 'password');
            $user = User::where(['email'=> $req['email']])->first();

            if ($user) 
            {
                if (Hash::check($req['password'], $user->password)) 
                {
                    $data = User::GetUserWithEmail($req['email']);
                    $permission = RolePermission::GetAllSmallByID(1000, 0, $data['role_id']);
                    $role = Role::where(['id' => $data['role_id']])->first();
                    $employee = null;
                    $shop = null;

                    if ($user->owner_id) {
                        $employee = Employee::where(['id' => $user->owner_id])->first();
                    }
                    
                    if ($employee) {
                        $shop = Shop::where(['id' => $employee->shop_id])->first();
                    }

                    $response = [
                        'message' => 'login success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => [
                            'user' => $data,
                            'role' => $role,
                            'permissions' => $permission,
                            'token' => $user->createToken('my-token')->plainTextToken,
                            'shop' => $shop,
                            'employee' => $employee
                        ]
                    ];
                }
                else 
                {
                    $response = [
                        'message' => 'check back your password',
                        'status' => 'password-invalid',
                        'code' => '201',
                        'data' => []
                    ];
                }
            }
            else 
            {
                $response = [
                    'message' => 'check back your email address',
                    'status' => 'email-invalid',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function loginUsername(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'username' => 'required|string|max:64',
            'password' => 'required|string|min:6'
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
            $user = User::where(['username'=> $req['username']])->first();

            if ($user) 
            {
                if (Hash::check($req['password'], $user->password)) 
                {
                    $data = User::GetUserWithUsername($req['username']);
                    $permission = RolePermission::GetAllSmallByID(1000, 0, $data['role_id']);
                    $role = Role::where(['id' => $data['role_id']])->first();
                    $employee = null;
                    $position = null;
                    $shop = null;

                    if ($user->owner_id) {
                        $employee = Employee::where(['id' => $user->owner_id])->first();
                    }
                    
                    if ($employee) {
                        $shop = Shop::where(['id' => $employee->shop_id])->first();
                        $position = Position::where(['id' => $employee->position_id])->first();
                    }

                    $response = [
                        'message' => 'login success',
                        'status' => 'ok',
                        'code' => '201',
                        'data' => [
                            'user' => $data,
                            'role' => $role,
                            'permissions' => $permission,
                            'token' => $user->createToken('my-token')->plainTextToken,
                            'shop' => $shop,
                            'employee' => $employee,
                            'position' => $position
                        ]
                    ];
                }
                else 
                {
                    $response = [
                        'message' => 'Password is incorrect',
                        'status' => 'password-invalid',
                        'code' => '201',
                        'data' => []
                    ];
                }
            }
            else 
            {
                $response = [
                    'message' => 'Username is incorrect',
                    'status' => 'username-invalid',
                    'code' => '201',
                    'data' => []
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function register(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:64|unique:users',
            'password' => 'required|string|min:6'
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
            $data = [
                'name' => $req['name'],
                'email' => $req['email'],
                'password' => Hash::make($req['password']),
                'username' => $req['username'],
                'status' => 'active',
                'enabled' => '1',
                'role_id' => '4'
            ];

            $rest = User::insert($data);
            if ($rest) 
            {
                $data = User::GetUserWithUsername($req['username']);
                $permission = RolePermission::GetAllSmallByID(1000, 0, $data['role_id']);
                $role = Role::where(['id' => $data['role_id']])->first();
                $employee = null;
                $shop = null;

                if ($data->owner_id) {
                    $employee = Employee::where(['id' => $data->owner_id])->first();
                }
                
                if ($employee) {
                    $shop = Shop::where(['id' => $employee->shop_id])->first();
                }

                $response = [
                    'message' => 'login success',
                    'status' => 'ok',
                    'code' => '201',
                    'data' => [
                        'user' => $data,
                        'role' => $role,
                        'permissions' => $permission,
                        'token' => $data->createToken('my-token')->plainTextToken,
                        'shop' => $shop,
                        'employee' => $employee
                    ]
                ];
            }
            else 
            {
                $response = [
                    'message' => 'register failed',
                    'status' => 'unauthorized',
                    'code' => '201',
                    'data' => []
                ];
            }
        }
     
        return response()->json($response, 200);
    }

    public function logout(Request $req)
    {
        $user = $req->user();
        $user->currentAccessToken()->delete();
        $response = [
            'message' => 'logout successfully',
            'status' => 'ok',
            'code' => '201',
            'data' => []
        ];

        return response()->json($response, 200);
    }

    public function me(Request $req)
    {
        $user = $req->user();
        $data = User::GetUserWithEmail(Auth()->user()->email);
        $permission = RolePermission::GetAllSmallByID(1000, 0, $data['role_id']);
        $role = Role::where(['id' => $data['role_id']])->first();
        $employee = null;
        $shop = null;

        if ($user->owner_id) {
            $employee = Employee::where(['id' => $user->owner_id])->first();
        }

        if ($employee) {
            $shop = Shop::where(['id' => $employee->shop_id])->first();
        }

        $response = [
            'message' => 'process success',
            'status' => 'ok',
            'code' => '201',
            'data' => [
                'user' => $data,
                'role' => $role,
                'permissions' => $permission,
                'shop' => $shop,
                'employee' => $employee
            ]
        ];
     
        return response()->json($response, 200);
    }
}
