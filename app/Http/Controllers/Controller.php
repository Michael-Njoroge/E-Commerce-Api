<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{

    public function sendResponse($result,$message,$code = 200)
    {
        $data = $result['data'] ?? [];

        if ($data !== []) {
            $result['data'] = $data;
        }
        $response = [
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ];


        return response()->json($response, $code);
    }

    public function sendError($error, $errorMessages = [], $code = 404)
    {

        $response = [
            'success' => false,
            'message' => $error,
        ];

        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);


    }
}