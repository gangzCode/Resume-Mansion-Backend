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

    public function getCart()
    {
        $user = auth()->user()->id;
        $input['location_id'] = 1;
        $transaction = Order::where('order_status', 3)->where('uid', $user)->first();
        $lines = [];
        if(isset($transaction))
        {
            $order_lines = OrderPackage::where('oid', $transaction->id)->get();
           
            $lines = [
                'order_id' => $transaction->id,
                'total' => $transaction->total_price,
                'currency_code' => $transaction->currency_symbol,
                'lines' => []
            ];
            foreach($order_lines as $line)
            {
                $package = DB::table('package')->where('id', $line->pid)->first();
                $addon = DB::table('addons')->where('id', $line->addon_id)->first();
                $data = [
                    'line_id' => $line->id,
                    'package_id' => $line->pid,
                    'package' => isset($package) ? $package->title : '',
                    'addon_id' => $line->addon_id,
                    'addon' => isset($addon) ? $addon->title : '',
                    'quantity' => $line->quantity,
                    'price' => $line->price ?? 0,
                ];

                array_push($lines['lines'], $data);
               

            }
        }

        if(!isset($transaction))
        {
            return response()->json([
                'transaction_id' => 0,
                'total_amount' => 0,
                'data' => [],
                'message' => 'Cart is empty',
            ], 200);
        }
        else 
        {
            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'transaction_id' => $transaction->id,
                'data' => $lines
            ], 200);
        }
    }

    public function update(Request $request)
    {
        $lines = $request->packages;
        $order_id = $request->order_id;
        $transaction = Order::find($order_id);
        $transaction->total_price = $request->final_total;
        $transaction->save();
        foreach($lines ?? [] as $key => $line)
        {
            if(isset($line['line_id']))
            {
                    $order_line = OrderPackage::find($line['line_id']);
                    $order_line->quantity = $line['quantity'];
                    $order_line->save();
            }
        }

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'transaction_id' => $transaction->id,
            'message' => 'Success Updated',
        ], 200);
    }

    public function delete($id)
    {
        $line = OrderPackage::find($id);
        
        if(isset($line))
        {
            $order_id = $line->oid;
            $order = Order::find($order_id);
            $lines = OrderPackage::where('oid', $order_id)->count();
            if($lines == 1)
            {
                $order->delete();
            }
            $line->delete();

            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'message' => 'Success Deleted',
            ], 200);

        }

        return response()->json([
            'http_status' => 404,
            'http_status_message' => 'warning',
            'message' => 'Bad Request',
        ], 404);
    }

    public function clear($id)
    {
        $order = Order::find($id);
        if(isset($order))
        {
            $lines = OrderPackage::where('oid', $order->id)->delete();
            $order->delete();
            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'message' => 'Success Deleted',
            ], 200);

        }
        return response()->json([
            'http_status' => 404,
            'http_status_message' => 'warning',
            'message' => 'Bad Request',
        ], 404);  

    }

    public function placeOrder(Request $request)
    {
        $order_id = $request->order_id;
        $user = auth()->user()->id;
        $transaction = Order::find($order_id);
        if(isset($transaction))
        {
            $transaction->order_status = '1';
            $transaction->payment_status = 'paid';
            $transaction->total_price = $request->final_total ? $request->final_total : $transaction->total_price;
    
            $transaction->save();
            $packages = $input['packages'];
            foreach ($packages ?? [] as $pr => $product) {
                if(isset($product['line_id']))
                {
                    $package = OrderPackage::find($product['line_id']);
                }
                else
                {
                    $package = new OrderPackage();
                }
                $package->oid = $transaction->id;
                $package->pid = $product['package_id'];
                $package->addon_id = $product['addon_id'];
                $package->quantity = $product['quantity'];
                $package->price = $product['amount'];
                $package->save();
                
            }
            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'message' => 'Added Successfully',
            ], 200);
        }
        return response()->json([
            'http_status' => 404,
            'http_status_message' => 'Warning',
            'message' => 'Transaction not Found',
        ], 404);
    }

    public function post(Request $request)
    {
        $order_id = $request->order_id;
        $user = auth()->user()->id;
        $transaction = Order::find($order_id);
        if(isset($transaction))
        {
            $transaction->order_status = '1';
            $transaction->payment_status = 'paid';
            $transaction->total_price = $request->final_total ? $request->final_total : $transaction->total_price;
    
            $transaction->save();
            $packages = $input['packages'];
            foreach ($packages ?? [] as $pr => $product) {
                if(isset($product['line_id']))
                {
                    $package = OrderPackage::find($product['line_id']);
                }
                else
                {
                    $package = new OrderPackage();
                }
                $package->oid = $transaction->id;
                $package->pid = $product['package_id'];
                $package->addon_id = $product['addon_id'];
                $package->quantity = $product['quantity'];
                $package->price = $product['amount'];
                $package->save();
                
            }
            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'message' => 'Added Successfully',
            ], 200);
        }
        return response()->json([
            'http_status' => 404,
            'http_status_message' => 'Warning',
            'message' => 'Transaction not Found',
        ], 404);
    }

    public function getPrevious()
    {
        $user = auth()->user()->id;
        $transactions = Order::whereNotIn('order_status', [3,1])->where('uid', $user)->get();
        $orders = [];
        foreach($transactions as $transaction)
        {
            $order_lines = OrderPackage::where('oid', $transaction->id)->unique()->pluck('pid')->toArray();
            $package = DB::table('package')->whereIn('id', $order_lines)->first();
            $status =DB::table('order_setps')->where('id', $transaction->order_status)->first();
           
            $lines = [
                'order_id' => $transaction->id,
                'name' => isset($package) ? $package->title : '',
                'ID' => 'ORDER #'.$transaction->id,
                'status' => isset($status) ? $status->step : ''
            ];
            array_push($orders, $lines);
        }

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'data'=> $orders
        ], 200);
    }
}
