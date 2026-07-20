<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuccessController extends Controller
{
    public function show(string $order)
    {
        $order = Order::where('uuid', $order)
            ->with(['items.product', 'items.variant', 'items.stockItem'])
            ->firstOrFail();

        abort_unless($order->status === 'paid', 403, 'O pedido ainda nÃ£o estÃ¡ liberado.');

        return response()->json([
            'order' => $order->uuid,
            'status' => $order->status,
            'items' => $order->items->map(function (OrderItem $item) use ($order) {
                $product = $item->product;

                return [
                    'product_name' => $product->name . ($item->variant ? ' (' . $item->variant->name . ')' : ''),
                    'delivery_type' => $product->delivery_type,
                    'post_purchase_instructions' => $product->post_purchase_instructions,
                    'file_url' => $product->delivery_type === 'file_download' && filled($product->file_path)
                        ? URL::temporarySignedRoute(
                            'orders.download',
                            now()->addHours(24),
                            ['order' => $order->uuid, 'item' => $item->id]
                        )
                        : null,
                    'keys' => $product->delivery_type === 'unique_key' && $item->stockItem
                        ? [$item->stockItem->value]
                        : [],
                ];
            }),
        ]);
    }

    public function download(string $order, int $item): StreamedResponse
    {
        $order = Order::where('uuid', $order)->firstOrFail();
        abort_unless($order->status === 'paid', 403, 'O pedido ainda nÃ£o estÃ¡ liberado.');

        $item = OrderItem::query()
            ->with('product:id,name,delivery_type,file_path')
            ->whereKey($item)
            ->where('order_id', $order->id)
            ->firstOrFail();

        $product = $item->product;
        abort_unless(
            $product->delivery_type === 'file_download' && filled($product->file_path),
            404,
            'Arquivo de entrega indisponÃ­vel.'
        );

        $disk = Storage::disk(config('filesystems.default'));
        abort_unless($disk->exists($product->file_path), 404, 'Arquivo de entrega indisponÃ­vel.');

        return $disk->download($product->file_path, basename($product->file_path));
    }
}
