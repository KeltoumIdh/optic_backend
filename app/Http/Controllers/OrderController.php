<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
// use Dompdf\Dompdf;
// use Barryvdh\DomPDF\PDF as DomPDFPDF;
// use Spatie\Browsershot\Browsershot;
// use Spatie\LaravelPdf\Facades\Pdf;
// use Spatie\Pdf\Pdf;
// use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;


class OrderController extends Controller
{

    public function index(Request $request)
    {
        $searchQuery = $request->input('query');
        $status = $request->input('status');

        $ordersQuery = Order::query();

        if (!empty($searchQuery)) {
            $ordersQuery->whereHas('client', function ($q) use ($searchQuery) {
                $q->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('lname', 'like', '%' . $searchQuery . '%')
                    ->orWhere('phone', 'like', '%' . $searchQuery . '%');
            });
        }

        // Filter by status if provided
        if (!empty($status)) {
            if ($status == 'credit') {
                $ordersQuery->where('is_credit', 1);
            } elseif ($status == 'notCredit') {
                $ordersQuery->where('is_credit', 0);
            }
        }

        $orders = $ordersQuery->with('client')->latest()->paginate(15);

        return response()->json($orders, 200);
    }
    // public function generateInvoice($orderId)
    // {
    //     $order = Order::find($orderId);
    //     if (!$order) {
    //         return response()->json(['error' => 'Order not found'], 404);
    //     }

    //     $pdf = Pdf::loadView('pdf',['order' => $order])->format('a4')
    //     ->save('invoice.pdf');
    //     // return $pdf->download();
    //     return $pdf;
    // }
    public function generateInvoice($orderId)
    {
        $order = Order::find($orderId);

        $products = [];
        if ($order->cart) {
            $cartData = json_decode($order->cart);
            if ($cartData && isset($cartData->productsCart)) {
                $productIds = collect($cartData->productsCart)->pluck('product_id')->toArray();
                $products = Product::whereIn('id', $productIds)->get();
            }
        }
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $data = [
            'title' => 'Page Title Here....',
            'date' => date('m/d/Y'),
            'order' => $order,
            'products' => $products
        ];

        $dompdf = new Dompdf();

        // Load HTML content from a blade view
        $html = view('pdf.view', $data)->render();

        // Set options (optional)
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        // Apply the options
        $dompdf->setOptions($options);

        // Load HTML into Dompdf
        $dompdf->loadHtml($html);

        // Render the PDF
        $dompdf->render();

        // Generate a unique file name for the PDF
        $filename = 'assets/uploads/pdf/' . 'document_' . time() . '.pdf';
        $name = 'document_' . time() . '.pdf';
        // Save the PDF file to the public directory
        $path = public_path($filename);
        file_put_contents($path, $dompdf->output());

        //insert doc in order table
        $order->invoice = $name;
        $order->save();
        // Optionally, you can store the file using Laravel's filesystem
        // Storage::disk('public')->put($filename, $dompdf->output());

        // Return a response with a download link
        return response()->json(asset($filename));
    }
    public function viewInvoice($orderId)
    {
        $order = Order::find($orderId);
        return view('pdf', compact('order'));
    }
    public function create(Request $request)
    {
        $query = $request->input('query');
        $status = $request->input('status');

        $clientsQuery = Client::query();

        if (!empty($query)) {
            $clientsQuery->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('lname', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            });
        }
        // Filter by status if provided
        if (!empty($status)) {
            $clientsQuery->where('status', $status);
        }

        $clients = $clientsQuery->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($clients);
        }

        // If no query or status, return all clients
        if (empty($query) && empty($status)) {
            $allClients = Client::paginate(10);
        }


        return response()->json($clients ?? [], 200);
    }

    public function createOrder($clientId, Request $request)
    {
        $client = Client::find($clientId);

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

        $products = $productsQuery->where('status', '!=', 'Rupture de stock')->paginate(10);

        return response()->json([
            'data' => $products,
            'client' => $client,
        ], 200);
    }
    public function getProductsByIds(Request $request)
    {
        $selectedProductIds = $request->input('selectedProductIds', []);

        $products = Product::whereIn('id', $selectedProductIds)->get();

        return response()->json(['products' => $products]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation rules
        $rules = [
            'client_id' => 'required|integer',
            'cart' => 'required|array',
            'payment_method' => 'required|string|in:cash,credit,check,traita',
            'date_fin_credit' => 'nullable|date', // Validate if provided and is a date
            'paid_price' => 'required|numeric|min:0',
            'remain_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'reference_credit' => 'nullable|string', // Validate if provided and is a string
            'file' => 'nullable|string',
            'client_traita' => 'nullable|string',
            'traita_date' => 'nullable|date',
        ];

        // Custom error messages
        $messages = [
            'payment_method.in' => 'Invalid payment method selected.',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);

        // Check for validation errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filePath = null;
        if ($request->has('file')) {
            $filePath = $this->uploadBase64Image($request->input('file'), 'uploads/files/');
        }

        $order = new Order();
        $order->client_id = $request->input('client_id');
        $order->cart = json_encode($request->input('cart'));
        $order->payment_method = $request->input('payment_method');
        $order->is_credit = $request->input('isCredit');
        $order->date_fin_credit = $request->input('date_fin_credit');
        $order->reference_credit = $request->input('reference_credit');
        $order->paid_price = $request->input('paid_price');
        $order->remain_price = $request->input('remain_price');
        $order->date_debut_credit = Carbon::now();
        $order->total_price = $request->input('total_price');
        $order->traita_date = $request->input('traita_date');

        if (!empty($filePath)) {
            $order->payement_file = $filePath;
        } else {
            $order->payement_file = 'default.jpg';
        }

        $order->client_traita = $request->input('client_traita');
        if ($request->input('is_credit') === false) {
            $order->payment_status = 'completed';
        } else {
            $order->payment_status = 'pending';
        }
        // Check if the payment is made in full
        if ($request->input('paid_price') >= $request->input('total_price')) {
            $order->payment_status = 'completed';
            $order->order_status = 'completed';
        } else {
            $order->payment_status = 'pending';
            $order->order_status = 'processing';
        }


        // payment_status:
        // Pending: The payment has been initiated but not completed.
        // Completed: The payment has been successfully processed.
        // Failed: The payment processing has failed.

        // order_status:
        // Processing: The order has been received and is being processed.
        // Shipped: The order has been shipped to the customer.
        // Delivered: The order has been successfully delivered to the customer.
        // Cancelled: The order has been cancelled.


        if ($order->save())
        {
            
            $this->saveThisMove([
                "type" => 'order_1',
                "data" => $order->only('id', 'client_id', 'cart')
            ]);


            // update the ordered products
            $this->updateProductQNT($request->cart["productsCart"]);
        }



        // return redirect('/orders')->with('status', 'Order created successfully');
        return response()->json(['status' => 'Order created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::with('client')->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $products = [];
        if ($order->cart) {
            $cartData = json_decode($order->cart);
            if ($cartData && isset($cartData->productsCart)) {
                $productIds = collect($cartData->productsCart)->pluck('product_id')->toArray();
                $products = Product::whereIn('id', $productIds)->get();
            }
        }

        return response()->json(['order' => $order, 'products' => $products], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {

        $product = Order::find($id);
        return response()->json($product ?? [], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // validation
        $request->validate([
            'payment_method' => 'required|string|in:cash,credit,check,traita',
            'date_fin_credit' => 'nullable|date',
            'paid_price' => 'required|numeric|min:0',
            // 'remain_price' => 'required|numeric|min:0',
            // 'total_price' => 'required|numeric|min:0',
            'reference_credit' => 'nullable|string',
            'payement_file' => 'nullable|string',
            'status' => 'required|string',
        ]);


        // Find Product by id
        $order = Order::findOrFail($id);


        $filePath = null;
        if ($request->has('payement_file')) {
            $filePath = $this->uploadBase64Image($request->input('payement_file'), 'uploads/files/');
        }
        
        if (!empty($filePath)) {
            $order->payement_file = $filePath;
        }

        // Update other fields
        $order->payment_method = $request->input('payment_method');
        $order->date_fin_credit = $request->input('date_fin_credit');
        $order->paid_price = $request->input('paid_price');
        $order->remain_price = $order->total_price - $request->input('paid_price');
        $order->reference_credit = $request->input('reference_credit');

        // // Check if the payment is made in full
        // if ($request->input('paid_price') >= $order->total_price) {
        //     $order->payment_status = 'completed';
        //     $order->order_status = 'completed';
        // } else {
        //     $order->payment_status = 'pending';
        //     $order->order_status = 'processing';
        // }

        $order->order_status = $request->input('status');

        // Save the order instance
        $order->save();
        
        
        $this->saveThisMove([
            "type" => 'order_2',
            "data" => $order->only('id')
        ]);


        return response()->json($order, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
    public function updateSelectedProducts(Request $request)
    {
        $selectedProducts = $request->input('selectedProducts', []);
        session(['selectedProducts' => $selectedProducts]);

        return response()->json(['status' => 'success']);
    }

    public function search_products(Request $request)
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

        return view('admin.orders.partial.products_table', compact('products'));
    }

    public function clients_search(Request $request)
    {

        $query = $request->input('query');
        $status = $request->input('status');

        $productsQuery = Client::query();

        if (!empty($query)) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('lname', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%");
            });
        }
        //credit
        if (!empty($status)) {
            $productsQuery->where('status', $status);
        }

        $clients = $productsQuery->paginate(10);

        return view('admin.orders.create', compact('clients'));
    }
}