<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class ReviewController extends Controller
{
    public function acceptReview()
    {
        $reviews = DB::table('review')->where('status', 1)->get();
        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'data' => $reviews
        ], 200);
    }

    public function pendingReview()
    {
        $reviews = DB::table('review')->where('status', 1)->get();
        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'data' => $reviews
        ], 200);
    }
}
