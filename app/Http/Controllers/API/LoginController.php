<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
class LoginController extends Controller
{
    public $successStatus = 200;
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'full_name' => 'required',
            'email' => 'required|unique:user',
            'contact_no' => 'required|numeric|digits:10|unique:user',
            'password' => 'required', 
            'password_confirmation' => 'required|same:password'
        ]);

        if($validator->fails()) {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }
        $user = new User();
        $data = $request->except('password_confirmation');
        $data['password'] = bcrypt($request->password);
        $data['type'] = 'System';
        $data['status'] = '1';
        $data['email_verified_at'] = date('Y-m-d h:i:s');
        $created_user = $user->create($data);
        

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'message' => 'Registration successful',
            'data' =>  $created_user
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        if(Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
    
            if($user->status != '1') {
                return response()->json([
                    'http_status' => 401,
                    'http_status_message' => 'Unauthorized',
                    'message' => 'Login failed',
                    'error' => ['info' => 'Account disabled']
                ], 401);
            }

            if($user->status == '1')
            {
                $token = $user->createToken('MyMyApp')->accessToken;
                return response()->json([
                    'http_status' => 200,
                    'http_status_message' => 'Success',
                    'message' => 'Login successful',
                    'data' => [
                        'token' => $token,
                        'info' => $user
                        ]
                ], 200);
            }
            else {
                return response()->json([
                    'http_status' => 401,
                    'http_status_message' => 'Unauthorized',
                    'message' => 'Login failed',
                    'error' => ['info' => 'You don\'t have access']
                ], 401);
            }
        }
        else {
            return response()->json([
                'http_status' => 401,
                'http_status_message' => 'Unauthorized',
                'message' => 'Login failed',
                'error' => ['info' => 'Invalid password']
            ], 401);
        }
    }
}
