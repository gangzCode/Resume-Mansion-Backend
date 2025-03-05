<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Mail\RegisterAccount;
use App\Mail\ForgotPassword;
use App\Mail\ResendOTP;
use App\Models\APIPasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Str;
use Carbon\Carbon;
use App\Models\Setting;
class PassportAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required|numeric|digits:10|unique:users',
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
        $activation_code = random_int(100000, 999999);
        $user = new User();
        $data = $request->except('password_confirmation');
        $data['password'] = bcrypt($request->password);
        $data['role'] = 'customer';
        $data['activation_code'] = $activation_code;
        $data['customer_type'] = 'online';
        $data['status'] = 2;
        $created_user = $user->create($data);
        $setting = Setting::find(1);
        $logo_url = isset($setting->logo) ? asset("storage/". $setting->logo) : asset("img/default.png");
        $mail_data = [
            'name' => $created_user->first_name . ' ' . $created_user->last_name,
            'activation_code' => $activation_code,
            'logo' => $logo_url
        ];

        Mail::to([$created_user->email])->send(new RegisterAccount($mail_data));

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'message' => 'Registration successful',
            'data' => ['email' => $created_user->email]
        ], 200);
    }
    
    public function otpVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'activation_code' => 'required|numeric|digits:6'
        ]);

        if($validator->fails()) {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->where('status', '1')->first();

        if($user->email_verified_at == null) {
            if($request->activation_code == $user->activation_code){ 
                $user->email_verified_at = Carbon::now();
                $user->save();

                $token = $user->createToken('MyMyApp')->accessToken;

                return response()->json([
                    'http_status' => 200,
                    'http_status_message' => 'Success',
                    'message' => 'Account verified and logged in',
                    'data' => [ 'token' => $token ]
                ], 200);
            } 
            else{ 
                return response()->json([
                    'http_status' => 400,
                    'http_status_message' => 'Bad Request',
                    'message' => 'Verification failed',
                    'error' => ['info' => 'The activation code is invalid']
                ], 400);
            }
        }
        else {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Verification failed',
                'error' => ['info' => 'Account already verified']
            ], 400);
        }
    }
    
    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users'
        ]);

        if($validator->fails()) {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->where('status', '1')->first();

        if($user->email_verified_at == null) {
            $activation_code = random_int(100000, 999999);
            $user->activation_code = $activation_code;
            $user->save();
            $setting = Setting::find(1);
            $logo_url = isset($setting->logo) ? asset("storage/". $setting->logo) : asset("img/default.png");
            $details = [
                'name' => $user->first_name . ' ' . $user->last_name,
                'activation_code' => $activation_code,
                'logo' => $logo_url 
            ];
    
            Mail::to([$user->email])->send(new ResendOTP($details));

            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'message' => 'OTP sent successfully'
            ], 200);
        }
        else {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Verification failed',
                'error' => ['info' => 'Account already verified']
            ], 400);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users'
        ]);

        if($validator->fails()) {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->where('status', '1')->first();
        $token = Str::random(60);
        $code = random_int(100000, 999999);

        $password_reset = new APIPasswordReset();
        $data['email'] = $user->email;
        $data['code'] = $code;
        $data['token'] = $token;
        $password_reset->create($data);
        $setting = Setting::find(1);
        $logo_url = isset($setting->logo) ? asset("storage/". $setting->logo) : asset("img/default.png");
        $mail_data = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'code' => $code,
            'logo' => $logo_url 
        ];

        Mail::to([$user->email])->send(new ForgotPassword($mail_data));

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'message' => 'Reset code sent successfully',
            'data' => [
                'token' => $token,
                'email' => $user->email
            ]
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'email' => 'required|email|exists:api_password_resets|exists:users',
            'code' => 'required|exists:api_password_resets',
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

        $check = APIPasswordReset::where('email', $request->email)->where('code', $request->code)->latest()->first();

        if($check) {
            $user = User::where('email', $request->email)->first();
            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'message' => 'Password changed successfully'
            ], 200);
        }
        else {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Password reset failed',
                'error' => ['info' => 'No request for the password change']
            ], 400);
        }
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

            if($user->role == 'customer') {
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

    public function logout(Request $request)
    {
        Auth::user()->token()->revoke();

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'message' => 'Logout successful'
        ], 200);
    }
}