<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * get Auth User Data
     */
    public function authUser()
    {
        return Auth::user();
    }


    /**
     * handle success & error messages
     * @param string $message
     * @param array $data
     * @param boolean $is_ok
    */
    function res($message = null, $data = [], $is_ok = true)
    {
        return response()->json([
            'is_ok' => $is_ok ? true : false,
            'message' => $message,
            'data' => $data,
        ], $is_ok ? 200 : 400);
    }
}
