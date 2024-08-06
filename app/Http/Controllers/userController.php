<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;

class UserController extends Controller
{

    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }


    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:admin,owner', // Adjust validation for role
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:255', // Adjust password validation as needed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = new User(); // Assuming User is your model for users

        // Set user attributes
        $user->name = $request->input('name');
        $user->role = $request->input('role');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password')); // Hash the password

        // Save the user
        $user->save();


        $this->saveThisMove([
            "type" => 'user_1',
            "data" => [
                "new_data" => $user->only('id','name','role','email'),
                "old_data" => [],
            ]
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User added successfully',
            'data' => $user,
        ], 201);
    }


    public function edit($id)
    {

        $user = User::findOrFail($id);
        return response()->json($user ?? [], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $userCurrentData = clone $user;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|max:255',
        ]);

        $user->update($request->all());

        $this->saveThisMove([
            "type" => 'user_2',
            "data" => [
                "new_data" => $user->only('id','name','role','email','password'),
                "old_data" => $userCurrentData->only('id','name','role','email','password'),
            ]
        ]);

        return response()->json($user, 200);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        $this->saveThisMove([
            "type" => 'user_3',
            "data" => [
                "new_data" => $user->only('id'),
                "old_data" => [],
            ]
        ]);

        return response()->json(null, 204);
    }

    public function updatePassword(Request $request)
    {
        $user = $this->authUser();

        // Vérifiez l'ancien mot de passe
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 400);
        }

        // Mettez à jour le mot de passe avec le nouveau mot de passe
        $user->password = Hash::make($request->password);
        $user->save();

        $this->saveThisMove([
            "type" => 'user_4',
            "data" => [
                "new_data" => $user->only('id'),
                "old_data" => [],
            ]
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }
}