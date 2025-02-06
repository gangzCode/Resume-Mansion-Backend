<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Document;
use Illuminate\Support\Facades\Validator;
class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'doc' => 'required',
        ]);

        if($validator->fails()) {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }
        
        $doc = new Document();
        $doc->note = $request->note;
        $doc->note = $request->note;
        if ($request->hasFile('doc') && $request->file('doc')->isValid()) {
            // if ($request->image->getSize() <= config('constants.image_size_limit')) {
                $new_file_name = time() . '_'  .$request->doc->getClientOriginalName();
                $image_path = '/public/cv/';
                $path = $request->doc->storeAs($image_path, $new_file_name);
                if ($path) {
                    $doc->document = $new_file_name;
                }
            // }
        }

        $doc->save();
        

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'message' => 'Upload successful',
            'data' =>  $doc
        ], 200);
    }
}
