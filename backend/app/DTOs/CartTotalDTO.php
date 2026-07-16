<?php

namespace App\DTOs;

class CartTotalDTO
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $total,
        public readonly ?string $couponCode,
        public readonly array $errors = []
    ) {}
}
