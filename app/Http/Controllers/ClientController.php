<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientController extends Controller
{

    // public function index(Request $request)
    // {
    //     $query = $request->input('query');
    //     $status = $request->input('status');

    //     $clientsQuery = Client::query();

    //     if (!empty($query)) {
    //         $clientsQuery->where(function ($q) use ($query) {
    //             $q->where('name', 'LIKE', "%{$query}%")
    //                 ->orWhere('lname', 'LIKE', "%{$query}%")
    //                 ->orWhere('phone', 'LIKE', "%{$query}%")
    //                 ->orWhere('city', 'LIKE', "%{$query}%");
    //         });
    //     }
    //     // Filter by status if provided
    //     if (!empty($status)) {
    //         $clientsQuery->where('status', $status);
    //     }

    //     $clients = $clientsQuery->orderBy('created_at', 'desc')->paginate(50);

    //     if ($request->wantsJson()) {
    //         return response()->json($clients);
    //     }

    //     // If no query or status, return all clients
    //     if (empty($query) && empty($status)) {
    //         $allClients = Client::paginate(10);
    //     }

    //     return response()->json($clients ?? [], 200);
    // }

    public function index(Request $request)
    {
        $query = $request->input('query');
        $status = $request->input('status');

        $clientsQuery = Client::query();

        if (!empty($query)) {
            $clientsQuery->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('lname', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%")
                    ->orWhere('city', 'LIKE', "%{$query}%");
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
            $allClients = Client::paginate(10);
        }

        return response()->json($clients ?? [], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lname' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'image' => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // return [$request->all()];

        $client = new Client();


        $avatarPath = null;
        if ($request->has('image')) {
            $avatarPath = $this->uploadBase64Image($request->input('image'), 'uploads/products/');
        }


        $client->name = $request->input('name');
        $client->lname = $request->input('lname');
        $client->phone = $request->input('phone');
        $client->address = $request->input('address');
        $client->city = $request->input('city');
        $client->image = $avatarPath;

        $client->save();


        $this->saveThisMove([
            "type" => 'client_1',
            "data" => [
                "new_data" => $client->only('id','name','lname','phone','address','city'),
                "old_data" => [],
            ]
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Client added successfully',
            'data' => $client,
        ], 201);
    }

    public function edit($id)
    {
        $client = client::find($id);
        return response()->json($client ?? [], 200);
    }

    public function update($id, Request $request)
    {
        $client = client::find($id);
        $clientCurrentData = clone $client;

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'lname' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'image' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }


        $avatarPath = null;
        if ($request->has('image')) {
            $avatarPath = $this->uploadBase64Image($request->input('image'), 'uploads/products/');
        }

        $client->name = $request->input('name');
        $client->lname = $request->input('lname');
        $client->city = $request->input('city');
        $client->address = $request->input('address');
        $client->phone = $request->input('phone');

        if (!empty($avatarPath)) {
            $client->image = $avatarPath;
        }

        $client->update();


        $this->saveThisMove([
            "type" => 'client_2',
            "data" => [
                "new_data" => $client->only('id','name','lname','phone','city','address','image'),
                "old_data" => $clientCurrentData->only('id','name','lname','phone','city','address','image'),
            ]
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Client updated successfully',
            'data' => $client,
        ], 201);
    }
    public function delete($id)
    {
        $client = client::find($id);

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found',
            ], 404);
        }

        if ($client->image !== 'default.jpg') {
            $path = public_path('/assets/uploads/clients/' . $client->image);

            if (file_exists($path)) {
                unlink($path);
            }
        }

        $client->delete();


        $this->saveThisMove([
            "type" => 'client_3',
            "data" => [
                "new_data" => $client->only('id'),
                "old_data" => [],
            ]
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Client deleted successfully',
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
        $client = Client::find($id);

        // Retrieve all orders that contain the client
        $clientOrders = Order::where('client_id' , $id)->get();


        // You can customize the response format as needed
        return response()->json([
            'client' => $client,
            'orders' => $clientOrders
        ], 200);
    }


    public function search(Request $request)
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

        return view('admin.clients.index', compact('clients'));
    }
}