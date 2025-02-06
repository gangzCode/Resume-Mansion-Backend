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
        $package = DB::table('package')->where('id', $id)->first();

        if(isset($package))
        {
            return response()->json([
                'http_status' => 200,
                'http_status_message' => 'Success',
                'data' => isset($package->addons) ? json_decode($package->addons) : []
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
