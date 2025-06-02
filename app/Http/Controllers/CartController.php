<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'product_id' => 'required',
            'total_price' => 'required',
            'quantity' => 'required',
        ]);

        $cartItem = Cart::create([
            'client_id' => $request->input('client_id'),
            'product_id' => $request->input('product_id'),
            'total_price' => $request->input('total_price'),
            'quantity' => $request->input('quantity'),
        ]);

        return response()->json(['cartItem' => $cartItem]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Cart $cart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cart $cart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        //
    }
}