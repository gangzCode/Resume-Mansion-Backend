<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Order;
use App\Models\OrderPackage;
use App\Models\Addon;
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
        $package = DB::table('package')->where('id', $input['package_id'])->first();
        $transaction = new Order();
        $transaction->uid = $user;
        $transaction->package_id = $input['package_id'];
        $transaction->added_by = $user;
        $transaction->added_date = date('Y-m-d');
        $transaction->payment_status = 'due';
        $transaction->order_status = 3;
        $transaction->total_price = isset($package) ? $package->price : 0;
        $transaction->currency_symbol = '$';
        $transaction->currency = 'usd';
        $transaction->coupon = null;
        $transaction->save();
        
        $data = OrderPackage::where('oid', $transaction->id)->get();
        $data->map(function($item) {
            $item->price = (string)$item->price;
        });
        $date['order'] = $transaction;
        $date['order_line'] = $data; 

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
            $package = DB::table('package')->where('id', $transaction->package_id)->first();
            $lines = [
                'order_id' => $transaction->id,
                'total' => (string)$transaction->total_price,
                'currency_code' => $transaction->currency_symbol,
                'package_id' => $transaction->package_id,
                'package' => isset($package) ? $package->title : '',
                'lines' => []
            ];
            foreach($order_lines as $line)
            {
                $addon = DB::table('addons')->where('id', $line->addon_id)->first();
                $data = [
                    'line_id' => $line->id,
                    'addon_id' => $line->addon_id,
                    'addon' => isset($addon) ? $addon->title : '',
                    'description' => isset($addon) ? $addon->description : '',
                    'quantity' => $line->quantity,
                    'price' => (string)$line->price ?? "0,00",
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
        $input = $request->except('_token');
        $order_id = $request->order_id;
        $addon = Addon::find($input['addon_id']);
        $transaction = Order::find($order_id);
        $addon_price = $input['quantity'] * $addon->price;
        $transaction->total_price = $addon_price + $transaction->total_price;
        $transaction->save();
        $package = OrderPackage::where('addon_id', $input['addon_id'])->where('oid', $order_id)->first();
        if (!isset($package))
        {
            $package = new OrderPackage();
        }
        
            
        $package->oid = $transaction->id;
        $package->pid = $transaction->package_id;
        $package->addon_id = $input['addon_id'];
        $package->quantity = $input['quantity'];
        $package->price = $addon->price;
        $package->save();

        $lines = [];
        if(isset($transaction))
        {
            $order_lines = OrderPackage::where('oid', $transaction->id)->get();
            $package = DB::table('package')->where('id', $transaction->package_id)->first();
            $lines = [
                'order_id' => $transaction->id,
                'total' => (string)$transaction->total_price,
                'currency_code' => $transaction->currency_symbol,
                'package_id' => $transaction->package_id,
                'package' => isset($package) ? $package->title : '',
                'lines' => []
            ];
            foreach($order_lines as $line)
            {
                $addon = DB::table('addons')->where('id', $line->addon_id)->first();
                $data = [
                    'line_id' => $line->id,
                    'addon_id' => $line->addon_id,
                    'addon' => isset($addon) ? $addon->title : '',
                    'description' => isset($addon) ? $addon->description : '',
                    'quantity' => $line->quantity,
                    'price' => (string)$line->price ?? "0,00",
                ];

                array_push($lines['lines'], $data);
               

            }
        }
        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'transaction_id' => $transaction->id,
            'data' => $lines,
            'message' => 'Success Updated',
        ], 200);
    }

    public function delete($id)
    {
        $line = OrderPackage::find($id);
        
        if (!$line) {
            return response()->json([
                'http_status' => 404,
                'http_status_message' => 'warning',
                'message' => 'Bad Request',
            ], 404);
        }

        $order_id = $line->oid;
        $order = Order::find($order_id);

        if ($order) {
            // Reduce total price when removing an add-on
            $order->total_price -= ($line->quantity * $line->price);
            $order->save();
        }

        // Delete the specific add-on line
        $line->delete();

        // Check if there are any remaining add-ons in the order
        $remainingLines = OrderPackage::where('oid', $order_id)->count();

        // If no add-ons remain, set `lines` to an empty array but keep the order
        $lines = [];
        if ($remainingLines > 0) {
            $order_lines = OrderPackage::where('oid', $order_id)->get();
            $package = DB::table('package')->where('id', $order->package_id)->first();

            $lines = [
                'order_id' => $order->id,
                'total' => (string)$order->total_price,
                'currency_code' => $order->currency_symbol,
                'package_id' => $order->package_id,
                'package' => isset($package) ? $package->title : '',
                'lines' => []
            ];

            foreach ($order_lines as $orderLine) {
                $addon = DB::table('addons')->where('id', $orderLine->addon_id)->first();
                $data = [
                    'line_id' => $orderLine->id,
                    'addon_id' => $orderLine->addon_id,
                    'addon' => isset($addon) ? $addon->title : '',
                    'description' => isset($addon) ? $addon->description : '',
                    'quantity' => $orderLine->quantity,
                    'price' => (string)$orderLine->price ?? "0,00",
                ];

                array_push($lines['lines'], $data);
            }
        }

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'message' => 'Success Deleted',
            'data' => $lines, // Send updated lines array
        ], 200);
    }


    public function clear(Request $request)
    {
        $user = auth()->user()->id;
        $order = Order::where('order_status', 3)->where('uid', $user)->first();
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
            $transaction->coupon = $request->coupon_id;
            $transaction->save();
            $packages = $request->packages;
            $total = 0;
            foreach ($packages ?? [] as $pr => $product) {
                $addon = Addon::find($product['addon_id']);
                $package = OrderPackage::where('addon_id', $product['addon_id'])->where('oid', $transaction->id)->first();
                if(!isset($package))
                {
                    $package = new OrderPackage();
                }
                $package->oid = $transaction->id;
                $package->pid = $transaction->package_id;
                $package->addon_id = $product['addon_id'];
                $package->quantity = $product['quantity'];
                $package->price = $addon->price;
                $package->save();

                $total += $addon->price;
            }

            $package = DB::table('package')->where('id', $transaction->package_id)->first();
            $sub_total = $package->price + $total;
            if(isset($request->coupon_id))
            {
                $coupon = DB::table('coupon')->where('id', $request->coupon_id)->first();
                $sub_total = $sub_total - $coupon->price;
            }
            
            $transaction->total_price = $sub_total;
            $transaction->save();
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
            $transaction->coupon = $request->coupon_id;
            $transaction->save();
            $packages = $request->packages;
            $total = 0;
            foreach ($packages ?? [] as $pr => $product) {
                $addon = Addon::find($product['addon_id']);
                $package = OrderPackage::where('addon_id', $product['addon_id'])->where('oid', $transaction->id)->first();
                if(!isset($package))
                {
                    $package = new OrderPackage();
                }
                $package->oid = $transaction->id;
                $package->pid = $transaction->package_id;
                $package->addon_id = $product['addon_id'];
                $package->quantity = $product['quantity'];
                $package->price = $addon->price;
                $package->save();

                $total += $addon->price;
            }

            $package = DB::table('package')->where('id', $transaction->package_id)->first();
            $sub_total = $package->price + $total;
            if(isset($request->coupon_id))
            {
                $coupon = DB::table('coupon')->where('id', $request->coupon_id)->first();
                $sub_total = $sub_total - $coupon->price;
            }
            
            $transaction->total_price = $sub_total;
            $transaction->save();
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
