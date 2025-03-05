<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class SliderController extends Controller
{
    public function index() {
        $sliders = DB::table('slider')->get();

        $sliders->map(function($faq) {
            $faq->image = "http://localhost/frelance-main/admin/images/slider/".$faq->image;
            unset($faq->added_by);
            unset($faq->added_date);
        });

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'data' => $sliders
        ], 200);
    }
}
