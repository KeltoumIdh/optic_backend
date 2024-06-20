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



    public function getClientCredit()
    {
        $currentDate = now()->toDateString();
        $fiveDaysLater = now()->addDays(5)->toDateString(); // Date cinq jours plus tard
        
        $clients = DB::table('orders')
            ->join('clients', 'orders.client_id', '=', 'clients.id')
            ->whereNotNull('date_debut_credit')
            ->where(function ($query) use ($currentDate, $fiveDaysLater) {
                $query->whereDate('date_fin_credit', '>=', $currentDate) // Date de fin de crédit doit être après ou égale à la date actuelle
                      ->orWhereDate('date_fin_credit', '<=', $fiveDaysLater); // Date de fin de crédit doit être avant ou égale à cinq jours plus tard
            })
            // ->where('remain_price', '>', 0)
            ->orWhereDate('date_fin_credit', '<', $currentDate) // Date de fin de crédit est antérieure à la date actuelle
            
            ->select('clients.*', 'orders.*', DB::raw('DATEDIFF(date_fin_credit, CURDATE()) AS days_remaining'))
            ->distinct()
            ->get();
        
        return $clients;
    }
   

    public function getPaymentOrders()
    {
        $orders = DB::table('orders')
                    ->join('clients', 'orders.client_id', '=', 'clients.id')
                    ->whereNotNull('orders.payment_method')
                    ->whereIn('orders.payment_method', ['check', 'trita'])
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

