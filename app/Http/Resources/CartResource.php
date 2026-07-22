<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'image' => $item->product->image,
                    'price' => $item->product->price,
                    'sale_price' => $item->product->sale_price,
                    'stock' => $item->product->stock,
                ],
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'options' => $item->options,
                'subtotal' => round($item->unit_price * $item->quantity, 2),
            ]),
            'discount' => $this->discount ? [
                'code' => $this->discount->code,
                'type' => $this->discount->type,
                'value' => $this->discount->value,
            ] : null,
        ];
    }
}
