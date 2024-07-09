<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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


    /**
     * Upload Base64 Images
     */
    public function uploadBase64Image($file, $folder = "uploads/")
    {
        if ($file) {
            // Decode the base64 string
            $avatarDecoded = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $file));

            // Generate a new filename
            $avatarName =  date('Ymdhis').'_'.uniqid() . '.png';

            // Save the file to the desired location
            Storage::disk('public')->put($folder . $avatarName, $avatarDecoded);

            $file_path = Storage::url($folder . $avatarName);

            return $file_path ?? null;
        }

        return null;
    }
}
