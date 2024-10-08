<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{

    /**
     * Login Method
     */
    public function user(Request $request)
    {
        return Auth::user();
    }



    /**
     * Login Method
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|string",
            "password" => "required|string",
        ]);

        if ($validator->fails()) {
            return $this->res("", $validator->errors(), 0);
        }


        $credentials = $request->only(['email', 'password']);
        $success = [];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $success['user'] = $user;
            $success['token'] = $user->createToken("optic")->plainTextToken;
            return $this->res("Success!", $success, 1);
        }


        return $this->res("Invalid email address or password", [], 0);
    }



    /**
     * Logout Method
     */
    public function logout(Request $request)
    {
        // Auth::guard()->logout();
        $user = Auth::user();
        $user->tokens()->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->res("Success!", [], 1);
    }
}
