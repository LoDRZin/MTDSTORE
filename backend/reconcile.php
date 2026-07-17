<?php

$ordersSemEntrega = \App\Models\Order::where('status', 'paid')
    ->whereHas('items', fn ($q) => $q->whereNull('stock_item_id'))
    ->get();

echo "Pedidos sem entrega: " . $ordersSemEntrega->count() . PHP_EOL;

foreach ($ordersSemEntrega as $order) {
    echo "Pedido {$order->id} pago mas sem chave alocada — verificar manualmente." . PHP_EOL;
}
