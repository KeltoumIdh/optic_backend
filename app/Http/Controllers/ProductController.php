<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('query');
        $status = $request->input('status');

        $productsQuery = Product::query();

        if (!empty($query)) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('reference', 'LIKE', "%{$query}%");
            });
        }
        // Filter by status if provided
        if (!empty($status)) {
            $productsQuery->where('status', $status);
        }

        $products = $productsQuery->orderBy('created_at', 'desc')->paginate(50);

        if ($request->wantsJson()) {
            return response()->json($products);
        }

        // If no query or status, return all products
        if (empty($query) && empty($status)) {
            $allProducts = Product::paginate(10);
        }

        return response()->json($products ?? [], 200);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'reference' => 'required|string|max:255',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'message' => 'nullable|string',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $product = new Product();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = $file->getClientOriginalExtension();
            $filename = time() . '.' . $ext;
            $file->move('assets/uploads/products/', $filename);
            $product->image = $filename;
        } else {
            $product->image = 'default.jpg';
        }

        $product->name = $request->input('name');
        $product->reference = $request->input('reference');
        $product->price = $request->input('price');
        $quantity = $request->input('quantity');

        $product->initial_quantity = $quantity;
        $product->quantity_available = $quantity;
        $product->quantity_sold = 0;

        $low_stock_threshold = 0.1 * $quantity; // 10% of initial quantity
        if ($quantity == 0) {
            $status = 'Rupture de stock';
        } elseif ($quantity <= $low_stock_threshold) {
            $status = 'Stock faible';
        } else {
            $status = 'Disponible';
        }

        $product->status = $status;
        $product->message = $request->input('message');
        $product->save();

        return  response()->json([
            'status' => 'success',
            'message' => 'Product added successfully',
            'data' => $product,
        ], 201);
    }

    public function edit($id)
    {

        $product = product::find($id);
        return response()->json($product ?? [], 200);
    }

    public function update(Request $request, $id)
    {
        // validation
        $request->validate([
            'name' => 'string|max:255',
            'reference' => 'string|max:255',
            'price' => 'numeric',
            'quantity_available' => 'integer',
            'message' => 'nullable|string',
            'quantity_sold' => 'nullable|integer',
            // 'image' => 'nullable|image|size:1024|mimes:jpeg,png,jpg,gif,svg',
        ]);


        // Find Product by id
        $product = Product::findOrFail($id);


        if ($request->hasFile('image')) {
            // Upload and save the new image
            $file = $request->file('image');
            $fileExt = $file->getClientOriginalExtension();

            // file type must be an image (jpeg, png, jpg)
            if (!in_array($fileExt, ['jpeg', 'png', 'jpg','PNG'])) {
                return response()->json('Try with another image', 200);
            }

            // create a new name
            $filename = time() . '.' . $fileExt;

            // store image with new name
            $file->move(public_path('assets/uploads/products/'), $filename);

            // Delete old image
            $existingImagePath = public_path('assets/uploads/products/' . $product->image);
            if (file_exists($existingImagePath)) {
                unlink($existingImagePath);
            }

            // Update the product's image
            $product->image = $filename;
        }

        // Update other fields
        $product->name = $request->input('name');
        $product->reference = $request->input('reference');
        $product->price = $request->input('price');
        $product->quantity_available = $request->input('quantity_available');
        $product->quantity_sold = $request->input('quantity_sold');

        // Update status based on quantity
        $quantity = $request->input('quantity_available');
        $low_stock_threshold = 0.1 * $product->initial_quantity; // 10% of initial quantity
        if ($quantity == 0) {
            $product->status = 'Rupture de stock';
        } elseif ($quantity <= $low_stock_threshold) {
            $product->status = 'Stock faible';
        } else {
            $product->status = 'Disponible';
        }

        $product->message = $request->input('message');

        // Save the product instance
        $product->save();

        return response()->json($product, 200);
    }

    public function delete($id)
    {
        $product = product::find($id);
        if ($product->image !== 'default.jpg') {
            $path = 'assets/uploads/products/' . $product->image;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $product->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully',
        ], 201);
    }

    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Retrieve the product details by its ID
        $product = Product::find($id);

        // Retrieve all orders that contain the product
        $productOrders = Order::whereJsonContains('cart->productsCart', ['product_id' => $id])->get();


        // You can customize the response format as needed
        return response()->json([
            'product' => $product,
            'orders' => $productOrders
        ], 200);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $status = $request->input('status');

        $productsQuery = Product::query();

        if (!empty($query)) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('reference', 'LIKE', "%{$query}%");
            });
        }

        if (!empty($status)) {
            $productsQuery->where('status', $status);
        }

        $products = $productsQuery->paginate(10);

        return view('admin.products.index', compact('products'));
    }
}