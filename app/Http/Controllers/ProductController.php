<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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
            $allProducts = Product::paginate(50);
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
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $product = new Product();


        $avatarPath = null;
        if ($request->has('image') && !empty($request->input('image'))) {
            $avatarPath = $this->uploadBase64Image($request->input('image'), 'uploads/products/');
        } else {
            // Set default image path when no image is provided
            $avatarPath = 'default.jpg';
        }


        $product->name = $request->input('name');
        $product->reference = $request->input('reference');
        $product->price = $request->input('price');
        $quantity = $request->input('quantity');
        $product->initial_quantity = $quantity;
        $product->quantity_available = $quantity;
        $product->quantity_sold = 0;
        $product->image = $avatarPath;
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


        $this->saveThisMove([
            "type" => 'product_1',
            "data" => [
                "new_data" => $product->only('id', 'name', 'reference', 'price'),
                "old_data" => [],
            ]
        ]);


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
            'image' => 'nullable|string',
        ]);


        // Find Product by id
        $product = Product::findOrFail($id);
        $productCurrentData = clone $product;


        $avatarPath = null;
        if ($request->has('image') && !empty($request->input('image'))) {
            $avatarPath = $this->uploadBase64Image($request->input('image'), 'uploads/products/');
        }

        // Update other fields
        $product->name = $request->input('name');
        $product->reference = $request->input('reference');
        $product->price = $request->input('price');
        $product->quantity_available = $request->input('quantity_available');
        $product->quantity_sold = $request->input('quantity_sold');

        if (!empty($avatarPath)) {
            $product->image = $avatarPath;
        } else if (!$product->image) {
            // If no image was provided and product doesn't have an image, set default
            $product->image = 'default.jpg';
        }

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


        $this->saveThisMove([
            "type" => 'product_2',
            "data" => [
                "new_data" => $product->only('id', 'name', 'reference', 'price', 'quantity_available', 'image'),
                "old_data" => $productCurrentData->only('id', 'name', 'reference', 'price', 'quantity_available', 'image'),
            ]
        ]);


        return response()->json($product, 200);
    }

    public function delete($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        }

        // Find orders that reference this product in their cart JSON
        $orders = Order::all();
        foreach ($orders as $order) {
            if ($order->cart) {
                $cartData = json_decode($order->cart, true);
                if ($cartData && isset($cartData['productsCart'])) {
                    // Remove the product from the productsCart array or mark it as deleted
                    $productsCart = $cartData['productsCart'];
                    $updatedProductsCart = array_filter($productsCart, function ($item) use ($id) {
                        return $item['product_id'] != $id;
                    });

                    // Update the cart JSON
                    $cartData['productsCart'] = array_values($updatedProductsCart);
                    $order->cart = json_encode($cartData);
                    $order->save();
                }
            }
        }

        if ($product->image) {
            // Handle deletion of image if it exists
            if ($product->image !== 'default.jpg') {
                // If it's a storage path
                if (strpos($product->image, '/storage/') === 0) {
                    // Convert /storage/ path to the actual storage path
                    $path = str_replace('/storage/', '', $product->image);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
                // If it's a legacy path
                else if (
                    strpos($product->image, 'assets/uploads/products/') === 0 ||
                    file_exists(public_path($product->image))
                ) {
                    @unlink(public_path($product->image));
                }
            }
        }

        $product->delete();

        $this->saveThisMove([
            "type" => 'product_3',
            "data" => [
                "new_data" => $product->only('id'),
                "old_data" => [],
            ]
        ]);

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

        $products = $productsQuery->paginate(50);

        return view('admin.products.index', compact('products'));
    }
}
