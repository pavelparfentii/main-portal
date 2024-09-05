<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Revolution\Google\Sheets\Facades\Sheets;

class GooglesheetPartnerController extends Controller
{
    public function index(Request $request)
    {
        $email = $request->get('email');
        $message = $request->get('message');

        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

//       $ad = env('GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION');
//       dd($ad);
        /** generate sheet name **/
        $sheetName = 'Sheet1';

        /** prepare the data in array **/
        $data = [
            [
                'email',
                'message',
            ],
            [
                $email,
                $message,
            ],
        ];

        Sheets::spreadsheet(config('google.post_spreadsheet_id'));
        /** write the data in the newly generated sheet **/
        Sheets::sheet($sheetName)->append($data);

    }
}
