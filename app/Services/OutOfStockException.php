<?php

declare(strict_types=1);

namespace App\Services;

final class OutOfStockException extends \RuntimeException
{
    public int $productId;
    public int $requestedQty;

    /**
     * Exception saat stok produk tidak mencukupi untuk quantity yang diminta.
     */
    public function __construct(int $productId, int $requestedQty, string $message = 'Stok tidak mencukupi.')
    {
        $this->productId = $productId;
        $this->requestedQty = $requestedQty;
        parent::__construct($message);
    }
}
