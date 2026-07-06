<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'suppliers' => Supplier::count(),
            'products' => Product::count(),
            'purchase_orders' => PurchaseOrder::count(),
            'users' => User::count(),
        ]);
    }
}
