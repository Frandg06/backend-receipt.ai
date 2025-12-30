<?php

namespace App\DataObjects;

use Illuminate\Contracts\Support\Arrayable;

final readonly class ProductData implements Arrayable
{
    public function __construct(
        public string $id,
        public string $name,
        public float $price,
        public int $quantity,
        public array $users,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            price: (float) $data['price'],
            quantity: (int) $data['quantity'],
            users: $data['users'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'users' => $this->users,
        ];
    }
}
