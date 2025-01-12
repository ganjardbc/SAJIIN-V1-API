<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenValidationController extends Controller
{
    //
    public function validateToken(Request $request)
    {
        // Check if the Authorization header is present
        if (!$request->hasHeader('Authorization')) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Unauthorized.',
            ], 401);
        }
        
        if (Auth::guard('sanctum')->check()) {
            return response()->json([
                'statusCode' => 200,
                'message' => 'Token is valid.',
            ], 200);
        } else {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Unauthorized.',
            ], 401);
        }
    }
}
