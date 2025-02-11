<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Order;
use App\Models\OrderPackage;
class CardController extends Controller
{
    public function getCoupon(Request $request)
    {
        $coupon = $request->get('coupon');
        if(!isset($coupon))
        {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Enter the Coupon'
            ], 400);
        }
        $date = date('Y-m-d');
        $check = DB::table('coupon')->where('coupon', $coupon)->where('status', 1)
        ->where('start_date', '<=', $date)->where('end_date', '>=', $date)->first();

        if(isset($check))
        {
            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'message' => 'Coupon Available',
                'data' => $check
            ], 200);
        }
        else{
            return response()->json([
                'http_status' => 404,
                'http_status_message' => 'Not Found',
                'message' => 'Coupon Not Found'
            ], 404);
        }
    }

    public function addCart(Request $request)
    {
 
        $input = $request->except('_token');
        $input['order_status'] = 3;
        
        $user = auth()->user()->id;
        $input['location_id'] = 1;
        $transaction = Order::where('order_status', 3)->where('uid', $user)->first();
        if(!isset($transaction))
        {
            $transaction = new Order();
            $transaction->uid = $user;
            $transaction->added_by = $user;
            $transaction->added_date = date('Y-m-d');
            $transaction->payment_status = 'due';
            $transaction->order_status = 3;
            $transaction->total_price = $input['final_total'];
            $transaction->currency_symbol = $input['currency_symbol'];
            $transaction->currency = $input['currency'];
            $transaction->coupon = $input['coupon_id'];
            $transaction->save();
        }
        
        $packages = $input['packages'];
        foreach ($packages ?? [] as $pr => $product) {
            $package = new OrderPackage();
            $package->oid = $transaction->id;
            $package->pid = $product['package_id'];
            $package->addon_id = $product['addon_id'];
            $package->quantity = $product['quantity'];
            $package->price = $product['amount'];
            $package->save();
        }
        $date['order'] = $transaction;
        $date['order_line'] = OrderPackage::where('oid', $transaction->id)->get();
        return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'data' => $date
            ], 200);
    }
}
