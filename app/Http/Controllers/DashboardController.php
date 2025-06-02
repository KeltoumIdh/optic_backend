<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function totalProducts()
    {
        try {

            $total = Product::count();

            return response()->json(['total' => $total]);
        } catch (\Exception $e) {

            return response()->json(['error' => 'Erreur lors de la récupération du total des produits.'], 500);
        }
    }

    public function totalClients()
    {
        try {

            $total = Client::count();

            return response()->json(['total' => $total]);
        } catch (\Exception $e) {

            return response()->json(['error' => 'Erreur lors de la récupération du total des clients.'], 500);
        }
    }

    public function totalUsers()
    {
        try {

            $total = User::count();

            return response()->json(['total' => $total]);
        } catch (\Exception $e) {

            return response()->json(['error' => 'Erreur lors de la récupération du total des Users.'], 500);
        }
    }

    public function totalOrders()
    {
        try {

            $total = Order::count();

            return response()->json(['total' => $total]);
        } catch (\Exception $e) {

            return response()->json(['error' => 'Erreur lors de la récupération du total des Orders.'], 500);
        }
    }




    public function getTopSellingProducts()
    {
        $topSellingProducts = Product::orderByDesc('quantity_sold')
            ->take(8)
            ->get();

        return $topSellingProducts;
    }

    public function getAvailableProducts()
    {
        $availableProducts = Product::whereIn('status', ['Stock faible', 'Rupture de stock'])->get();

        return $availableProducts;
    }



    public function getCreditStatistics()
    {
        $statistics = [
            'total_credit' => Order::where('is_credit', 1)
                ->sum('total_price'),

            'total_paid' => Order::where('is_credit', 1)
                ->sum('paid_price'),

            'total_remaining' => Order::where('is_credit', 1)
                ->sum('remain_price'),

            'total_clients' => Client::whereHas('orders', function ($query) {
                $query->where('is_credit', 1)
                    ->where('remain_price', '>', 0);
            })->count(),

            'overdue_amount' => Order::where('is_credit', 1)
                ->where('date_fin_credit', '<', now())
                ->where('remain_price', '>', 0)
                ->sum('remain_price'),

            'due_soon_amount' => Order::where('is_credit', 1)
                ->where('date_fin_credit', '>=', now())
                ->where('date_fin_credit', '<=', now()->addDays(5))
                ->where('remain_price', '>', 0)
                ->sum('remain_price')
        ];

        return response()->json($statistics);
    }

    public function getClientCredit()
    {
        $currentDate = now();

        // Get all clients with credit orders
        $clients = Client::select('clients.*')
            ->addSelect([
                // Total credit amount for each client
                'total_credit' => Order::selectRaw('SUM(total_price)')
                    ->whereColumn('client_id', 'clients.id')
                    ->where('is_credit', 1),

                // Total paid amount for credit orders
                'total_paid' => Order::selectRaw('SUM(paid_price)')
                    ->whereColumn('client_id', 'clients.id')
                    ->where('is_credit', 1),

                // Total remaining amount for credit orders
                'total_remaining' => Order::selectRaw('SUM(remain_price)')
                    ->whereColumn('client_id', 'clients.id')
                    ->where('is_credit', 1),

                // Latest credit due date
                'latest_due_date' => Order::select('date_fin_credit')
                    ->whereColumn('client_id', 'clients.id')
                    ->where('is_credit', 1)
                    ->whereNotNull('date_fin_credit')
                    ->orderByDesc('date_fin_credit')
                    ->limit(1),

                // Days remaining until latest due date
                'days_remaining' => Order::selectRaw('DATEDIFF(MAX(date_fin_credit), CURDATE())')
                    ->whereColumn('client_id', 'clients.id')
                    ->where('is_credit', 1)
                    ->whereNotNull('date_fin_credit')
            ])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('client_id', 'clients.id')
                    ->where('is_credit', 1)
                    ->where('remain_price', '>', 0);
            })
            ->having('total_remaining', '>', 0)
            ->orderBy('days_remaining')
            ->get();

        // Add credit status for each client
        foreach ($clients as $client) {
            if ($client->days_remaining === null) {
                $client->credit_status = 'no_due_date';
            } else if ($client->days_remaining < 0) {
                $client->credit_status = 'overdue';
            } else if ($client->days_remaining <= 5) {
                $client->credit_status = 'due_soon';
            } else {
                $client->credit_status = 'on_time';
            }
        }

        return $clients;
    }


    public function getPaymentOrders()
    {
        $orders = DB::table('orders')
            ->join('clients', 'orders.client_id', '=', 'clients.id')
            ->whereNotNull('orders.payment_method')
            ->whereIn('orders.payment_method', ['check', 'traita'])
            ->select('clients.*', 'orders.*')
            ->get();

        return $orders;
    }



    public function getFactureOrders()
    {
        $orders = DB::table('orders')
            ->join('clients', 'orders.client_id', '=', 'clients.id')
            ->select('clients.*', 'orders.*')
            ->get();

        return $orders;
    }
}
