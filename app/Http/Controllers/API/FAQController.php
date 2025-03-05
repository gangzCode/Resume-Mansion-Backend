<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
class FAQController extends Controller
{
    public function faqs() {
        $faqs = DB::table('fandq')->get();

        $faqs->map(function($faq) {
            unset($faq->added_by);
            unset($faq->added_date);
            unset($faq->last_modified_by);
            unset($faq->last_modified_date);
        });

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'data' => $faqs
        ], 200);
    }
}
