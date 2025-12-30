<?php

namespace App\DataObjects;

use Illuminate\Contracts\Support\Arrayable;

final readonly class TicketData implements Arrayable
{
    private const ERROR_PREFIX = 'ERR_';

    public function __construct(
        public string $nombre,
        public float $total,
        public array $products,
    ) {
    }

    public static function fromArray(array $data): self
    {
        if (self::isError($data)) {
            throw new \InvalidArgumentException('Invalid ticket data: contains error');
        }

        return new self(
            nombre: $data['nombre'],
            total: (float) $data['total'],
            products: array_map(
                fn (array $product) => ProductData::fromArray($product),
                $data['products']
            ),
        );
    }

    public static function fromResponse(array $response): self|ErrorData
    {
        if (self::isError($response)) {
            return ErrorData::fromArray($response['error']);
        }

        return self::fromArray($response);
    }

    private static function isError(array $data): bool
    {
        return isset($data['error']) && is_array($data['error']);
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'total' => $this->total,
            'products' => array_map(
                fn (ProductData $product) => $product->toArray(),
                $this->products,
            ),
        ];
    }
}
