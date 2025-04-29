<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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

        $orders = $ordersQuery->with('client')->latest()->paginate(50);

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

        $clients = $clientsQuery->withCount('orders') // Count the number of orders
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        foreach ($clients as $client) {
            // Fetch the number of orders made by the client
            $client->orders_count = Order::where('client_id', $client->id)->count();

            // Check if the client has any remaining credit
            $client->has_credit = Order::where('client_id', $client->id)
                ->where('is_credit', 1)
                ->exists();
        }
        if ($request->wantsJson()) {
            return response()->json($clients);
        }

        // If no query or status, return all clients
        if (empty($query) && empty($status)) {
            $allClients = Client::paginate(50);
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

        $products = $productsQuery->where('status', '!=', 'Rupture de stock')->paginate(50);

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
        $order->payement_file = $filePath;
        $order->client_traita = $request->input('client_traita');

        if ($request->input('is_credit') === false) {
            $order->payment_status = 'completed';
        } else {
            $order->payment_status = 'pending';
        }

        // Check if the payment is made in full
        if ($request->input('paid_price') >= $request->input('total_price')) {
            $order->payment_status = 'completed';
        } else {
            $order->payment_status = 'pending';
        }

        $order->order_status = 'in_delivery';

        // payment_status:
        // Pending: The payment has been initiated but not completed.
        // Completed: The payment has been successfully processed.
        // Failed: The payment processing has failed.

        // order_status:
        // Processing: The order has been received and is being processed.
        // Shipped: The order has been shipped to the customer.
        // Delivered: The order has been successfully delivered to the customer.
        // Cancelled: The order has been cancelled.

        if ($order->save()) {
            $order->load('client');

            $this->saveThisMove([
                "type" => 'order_1',
                "data" => [
                    "new_data" => array_merge(
                        $order->only('id', 'client_id', 'cart'),
                        ['client' => $order->client->only('id', 'name')] // Include client data
                    ),
                    "old_data" => [],
                ]
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
        $deletedProducts = [];

        if ($order->cart) {
            $cartData = json_decode($order->cart, true);
            if ($cartData && isset($cartData['productsCart'])) {
                // Extract IDs of non-deleted products
                $productIds = [];

                foreach ($cartData['productsCart'] as $item) {
                    // If the product is marked as deleted, add it to deleted products array
                    if (isset($item['is_deleted']) && $item['is_deleted'] === true) {
                        if (isset($item['product_data'])) {
                            $deletedProducts[] = array_merge($item['product_data'], [
                                'quantity' => $item['quantity'] ?? 1,
                                'is_deleted' => true
                            ]);
                        }
                    } else {
                        // Otherwise add to product IDs to fetch from database
                        $productIds[] = $item['product_id'];
                    }
                }

                // Fetch non-deleted products from the database
                if (!empty($productIds)) {
                    $dbProducts = Product::whereIn('id', $productIds)->get();

                    // Add quantity information to each product
                    foreach ($dbProducts as $product) {
                        foreach ($cartData['productsCart'] as $item) {
                            if ($item['product_id'] == $product->id) {
                                $product->quantity = $item['quantity'] ?? 1;
                                break;
                            }
                        }
                    }

                    $products = $dbProducts;
                }
            }
        }

        return response()->json([
            'order' => $order,
            'products' => $products,
            'deleted_products' => $deletedProducts
        ], 200);
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
        $orderCurrentData = clone $order;
        $orderCurrentData->load('client');

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
        if ($request->has('reference_credit')) {
            $order->reference_credit = $request->input('reference_credit');
        }

        // Check if the payment is made in full
        if ($request->input('paid_price') >= $request->input('total_price')) {
            $order->payment_status = 'completed';
        } else {
            $order->payment_status = 'pending';
        }

        $order->order_status = $request->input('status');

        if (!empty($request->input('status')) and $request->input('status') === "delivered") {
            $order->delivery_date = Carbon::now();
        }

        if (!empty($request->input('status')) and $request->input('status') === "canceled") {
            $order->delivery_date = null;
            $order->payment_status = 'failed';
        }

        // Save the order instance
        $order->save();


        $this->saveThisMove([
            "type" => 'order_2',
            "data" => [
                "new_data" => $order->only('id', 'paid_price', 'date_fin_credit', 'payement_file', 'order_status', 'reference_credit', 'payment_method'),
                "old_data" => $orderCurrentData->only('id', 'paid_price', 'date_fin_credit', 'payement_file', 'order_status', 'reference_credit', 'payment_method'),
            ]
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

        $products = $productsQuery->paginate(50);

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

        $clients = $productsQuery->paginate(50);

        return view('admin.orders.create', compact('clients'));
    }

    /**
     * Confirm order
     */
    public function confirmOrder(Request $request)
    {
        $orderId = $request->input('order_id');


        if (!empty($orderId)) {
            // Get order data
            $order = Order::find($orderId);
            $order->payment_status = 'completed';
            $order->order_status = 'completed';
            $order->paid_price = $order->total_price;
            $order->remain_price = '0.00';
            $order->date_fin_credit = null;
            $order->update();
        }


        return response()->json(['status' => 'success']);
    }
    // Add this method to your StatisticsController or create a new controller

    /**
     * Get statistics data for the dashboard based on timeframe
     *
     * @param string $timeframe (daily, weekly, monthly)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics($timeframe = 'daily')
    {
        // Validate timeframe
        if (!in_array($timeframe, ['daily', 'weekly', 'monthly'])) {
            return response()->json(['error' => 'Invalid timeframe'], 400);
        }

        // Set up date ranges for current and previous periods
        $now = Carbon::now();
        $currentStart = null;
        $currentEnd = $now;
        $previousStart = null;
        $previousEnd = null;

        switch ($timeframe) {
            case 'daily':
                $currentStart = $now->copy()->startOfDay();
                $previousStart = $now->copy()->subDay()->startOfDay();
                $previousEnd = $now->copy()->subDay()->endOfDay();
                break;
            case 'weekly':
                $currentStart = $now->copy()->startOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'monthly':
                $currentStart = $now->copy()->startOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // Calculate sales statistics
        $currentSales = Order::whereBetween('created_at', [$currentStart, $currentEnd])->sum('total_price');
        $previousSales = Order::whereBetween('created_at', [$previousStart, $previousEnd])->sum('total_price');
        $salesChange = $previousSales > 0 ? round((($currentSales - $previousSales) / $previousSales) * 100, 1) : 0;

        // Calculate orders statistics
        $currentOrders = Order::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $previousOrders = Order::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $ordersChange = $previousOrders > 0 ? round((($currentOrders - $previousOrders) / $previousOrders) * 100, 1) : 0;

        // Calculate new clients statistics
        $currentClients = Client::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $previousClients = Client::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $clientsChange = $previousClients > 0 ? round((($currentClients - $previousClients) / $previousClients) * 100, 1) : 0;

        // Calculate average order value
        $currentAvgOrder = $currentOrders > 0 ? ($currentSales / $currentOrders) : 0;
        $previousAvgOrder = $previousOrders > 0 ? ($previousSales / $previousOrders) : 0;
        $avgOrderChange = $previousAvgOrder > 0 ? round((($currentAvgOrder - $previousAvgOrder) / $previousAvgOrder) * 100, 1) : 0;

        return response()->json([
            'sales' => [
                'value' => $currentSales,
                'percentChange' => $salesChange
            ],
            'orders' => [
                'value' => $currentOrders,
                'percentChange' => $ordersChange
            ],
            'clients' => [
                'value' => $currentClients,
                'percentChange' => $clientsChange
            ],
            'averageOrder' => [
                'value' => $currentAvgOrder,
                'percentChange' => $avgOrderChange
            ]
        ]);
    }

    /**
     * Get financial statistics data for the dashboard
     *
     * @param string $timeframe (today, yesterday, this_week, last_week, this_month, last_month, this_year, last_year, all)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFinancialStats($timeframe = 'today')
    {
        // Add CORS headers to ensure the endpoint is accessible from any domain
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
        ];

        // Log the incoming request for debugging
        Log::info('Financial stats requested with timeframe: ' . $timeframe);
        Log::info('Request URL: ' . request()->fullUrl());
        Log::info('Request method: ' . request()->method());

        try {
            // Extract timeframe from any request format (eg "financial-stats/all", "api/financial-stats/all", etc)
            if (strpos($timeframe, '/') !== false) {
                $parts = explode('/', $timeframe);
                $timeframe = end($parts);
            }

            // Handle empty timeframe
            if (empty($timeframe)) {
                $timeframe = 'today';
            }

            // Normalize the 'all_time' parameter to 'all' for consistency
            if ($timeframe === 'all_time') {
                $timeframe = 'all';
            }

            // Validate timeframe
            $validTimeframes = [
                'today',
                'yesterday',
                'this_week',
                'last_week',
                'this_month',
                'last_month',
                'this_year',
                'last_year',
                'all'
            ];

            if (!in_array($timeframe, $validTimeframes)) {
                Log::warning('Invalid timeframe requested: ' . $timeframe);

                // For now, default to 'all' instead of returning an error
                $timeframe = 'all';
            }

            // Set up date ranges based on timeframe
            $query = Order::query();
            $now = Carbon::now();
            $previousQuery = Order::query();

            if ($timeframe !== 'all') {
                switch ($timeframe) {
                    case 'today':
                        $query->whereDate('created_at', $now->toDateString());
                        $previousQuery->whereDate('created_at', $now->copy()->subDay()->toDateString());
                        break;

                    case 'yesterday':
                        $query->whereDate('created_at', $now->copy()->subDay()->toDateString());
                        $previousQuery->whereDate('created_at', $now->copy()->subDays(2)->toDateString());
                        break;

                    case 'this_week':
                        $query->whereBetween('created_at', [
                            $now->copy()->startOfWeek(),
                            $now->copy()->endOfWeek()
                        ]);
                        $previousQuery->whereBetween('created_at', [
                            $now->copy()->subWeek()->startOfWeek(),
                            $now->copy()->subWeek()->endOfWeek()
                        ]);
                        break;

                    case 'last_week':
                        $query->whereBetween('created_at', [
                            $now->copy()->subWeek()->startOfWeek(),
                            $now->copy()->subWeek()->endOfWeek()
                        ]);
                        $previousQuery->whereBetween('created_at', [
                            $now->copy()->subWeeks(2)->startOfWeek(),
                            $now->copy()->subWeeks(2)->endOfWeek()
                        ]);
                        break;

                    case 'this_month':
                        $query->whereYear('created_at', $now->year)
                            ->whereMonth('created_at', $now->month);
                        $previousMonth = $now->copy()->subMonth();
                        $previousQuery->whereYear('created_at', $previousMonth->year)
                            ->whereMonth('created_at', $previousMonth->month);
                        break;

                    case 'last_month':
                        $lastMonth = $now->copy()->subMonth();
                        $query->whereYear('created_at', $lastMonth->year)
                            ->whereMonth('created_at', $lastMonth->month);
                        $twoMonthsAgo = $now->copy()->subMonths(2);
                        $previousQuery->whereYear('created_at', $twoMonthsAgo->year)
                            ->whereMonth('created_at', $twoMonthsAgo->month);
                        break;

                    case 'this_year':
                        $query->whereYear('created_at', $now->year);
                        $previousQuery->whereYear('created_at', $now->year - 1);
                        break;

                    case 'last_year':
                        $query->whereYear('created_at', $now->year - 1);
                        $previousQuery->whereYear('created_at', $now->year - 2);
                        break;
                }
            }

            // Clone the query for counters to avoid issues with aggregations
            $countQuery = clone $query;

            // Get total revenue statistics
            $totalRevenue = $query->sum('total_price');
            $paidAmount = $query->sum('paid_price');
            $pendingAmount = $totalRevenue - $paidAmount;

            // Get count statistics
            $totalOrders = $countQuery->count();
            $completedOrdersCount = $countQuery->where('payment_status', 'completed')->count();
            $pendingOrdersCount = $countQuery->where('payment_status', 'pending')->count();

            // Calculate percentage of paid vs total
            $paymentCompletionRate = $totalRevenue > 0 ? round(($paidAmount / $totalRevenue) * 100, 1) : 0;

            // For comparison, get previous period stats
            $previousTotalRevenue = $previousQuery->sum('total_price');
            $previousPaidAmount = $previousQuery->sum('paid_price');

            // Calculate percentage changes
            $revenueChange = $previousTotalRevenue > 0 ?
                round((($totalRevenue - $previousTotalRevenue) / $previousTotalRevenue) * 100, 1) : 0;

            $paidAmountChange = $previousPaidAmount > 0 ?
                round((($paidAmount - $previousPaidAmount) / $previousPaidAmount) * 100, 1) : 0;

            // Calculate pending change only if both current and previous have data
            $previousPendingAmount = $previousTotalRevenue - $previousPaidAmount;
            $pendingAmountChange = $previousPendingAmount > 0 ?
                round((($pendingAmount - $previousPendingAmount) / $previousPendingAmount) * 100, 1) : 0;

            // Ensure zero values for empty results
            $totalRevenue = $totalRevenue ?: 0;
            $paidAmount = $paidAmount ?: 0;
            $pendingAmount = $pendingAmount ?: 0;

            // Build the response data with the correct structure
            $responseData = [
                'totalRevenue' => [
                    'value' => $totalRevenue,
                    'percentChange' => $revenueChange
                ],
                'paidAmount' => [
                    'value' => $paidAmount,
                    'percentChange' => $paidAmountChange
                ],
                'pendingAmount' => [
                    'value' => $pendingAmount,
                    'percentChange' => $pendingAmountChange
                ],
                'paymentCompletionRate' => $paymentCompletionRate,
                'orderCounts' => [
                    'total' => $totalOrders,
                    'completed' => $completedOrdersCount,
                    'pending' => $pendingOrdersCount
                ],
                'timeframe' => $timeframe
            ];

            // Log the response for debugging
            Log::info('Financial stats response generated successfully');

            // Return the response with CORS headers
            return response()->json($responseData, 200, $headers);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in financial stats: ' . $e->getMessage());

            // Return an error response
            return response()->json([
                'error' => 'An error occurred while fetching financial statistics',
                'message' => $e->getMessage()
            ], 500, $headers);
        }
    }
}
