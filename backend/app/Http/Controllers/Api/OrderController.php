<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function lookup(Request $request, $uuid)
    {
        $request->validate(['email' => 'required|email']);
        
        $order = \App\Models\Order::where('uuid', $uuid)
            ->whereHas('customer', function($q) use ($request) {
                $q->where('email', $request->email);
            })->with(['items.product', 'items.stockItems'])->firstOrFail();

        if ($order->status !== 'paid') {
            return response()->json(['error' => 'Pedido não foi pago ou está cancelado'], 403);
        }

        \App\Jobs\DeliverDigitalProduct::dispatch($order);

        return response()->json([
            'message' => 'Se o pedido foi pago, o link de acesso foi reenviado para o seu e-mail.'
        ]);
    }
}
