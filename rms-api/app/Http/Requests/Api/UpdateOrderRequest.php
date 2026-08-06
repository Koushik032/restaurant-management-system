<?php

namespace App\Http\Requests\Api;

class UpdateOrderRequest extends StoreOrderRequest
{
    /**
     * Determine whether the authenticated user
     * can update an order.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
}