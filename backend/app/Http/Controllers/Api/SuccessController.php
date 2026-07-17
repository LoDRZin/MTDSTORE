<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SuccessController extends Controller
{
    public function show($uuid)
    {
        $order = \App\Models\Order::where('uuid', $uuid)->with(['items.product', 'items.variant', 'items.stockItems'])->firstOrFail();

        // No futuro, podemos adicionar lógica de `accessed_at` no `delivery_logs`.
        
        return response()->json([
            'order' => $order->uuid,
            'status' => $order->status,
            'items' => $order->items->map(function($item) {
                return [
                    'product_name' => $item->product->name . ($item->variant ? ' (' . $item->variant->name . ')' : ''),
                    'delivery_type' => $item->product->delivery_type,
                    'post_purchase_instructions' => $item->product->post_purchase_instructions,
                    'keys' => $item->stockItems->pluck('value') // Aqui o value é finalmente exposto e descriptografado!
                ];
            })
        ]);
    }
}
