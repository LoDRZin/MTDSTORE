<?php

namespace App\DTOs;

class CouponDTO
{
    public function __construct(
        public readonly string $code,
        public readonly float $discountValue,
        public readonly string $type
    ) {}
}
