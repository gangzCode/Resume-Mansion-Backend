<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class PackageController extends Controller
{
    public function index()
    {
        $packages = DB::table('package')->get();
        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'data' => $packages
        ], 200);
    }

    public function view($id)
    {
        $packages = DB::table('addons')->where('package_id', $id)->get();
        $packages->map(function($faq) {
            unset($faq->created_at);
            unset($faq->updated_at);
        });

        if($packages->count() > 0)
        {
            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'data' => $packages
            ], 200);
        }
        else
        {
            return response()->json([
                'http_status' => 404,
                'http_status_message' => 'Not Found',
            ], 200);
        }
       
    }
}
