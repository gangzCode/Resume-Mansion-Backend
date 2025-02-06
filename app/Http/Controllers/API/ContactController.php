<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactForm;
use Illuminate\Support\Facades\Validator;
class ContactController extends Controller
{
    public function post(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required',
            'body' => 'required', 
            'verifyCode' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'http_status' => 400,
                'http_status_message' => 'Bad Request',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }
        $contact = new ContactForm();
        $data = $request->only(['name', 'email','subject','body','verifyCode']);
        $new_contact = $contact->create($data);
        

        return response()->json([
            'http_status' => 200,
            'http_status_message' => 'Success',
            'message' => 'Contact successful',
            'data' =>  $new_contact
        ], 200);

    }
}
