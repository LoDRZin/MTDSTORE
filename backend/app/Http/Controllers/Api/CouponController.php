<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(protected \App\Services\CouponService $couponService) {}

    /**
     * POST /api/coupon/validate
     *
     * Body:
     *   - code         string    required  The coupon code
     *   - order_total  numeric   required  Total cart value before discount
     *   - product_ids  int[]     optional  Product IDs in cart (for restriction check)
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:50',
            'order_total'    => 'required|numeric|min:0',
            'product_ids'    => 'sometimes|array',
            'product_ids.*'  => 'integer|min:1',
        ]);

        try {
            $dto = $this->couponService->validate(
                $request->input('code'),
                (float) $request->input('order_total'),
                $request->input('product_ids', [])
            );

            return response()->json([
                'code'     => $dto->code,
                'discount' => $dto->discountValue,
                'type'     => $dto->type,
            ]);
        } catch (\Exception $e) {
            $statusCode = in_array($e->getCode(), [400, 404, 422]) ? $e->getCode() : 422;

            return response()->json([
                'error' => [
                    'code'     => 'INVALID_COUPON',
                    'message'  => $e->getMessage(),
                    'trace_id' => request()->header('X-Correlation-ID', uniqid()),
                    'details'  => [],
                ],
            ], $statusCode);
        }
    }
}
