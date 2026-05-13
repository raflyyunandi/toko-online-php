<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use PDO;

final class OrderService
{
    private Product $productModel;
    private Order $orderModel;
    private OrderItem $orderItemModel;

    private PDO $pdo;

    /**
     * Inisialisasi service untuk proses bisnis order (transaksi, stok, dan item).
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->orderItemModel = new OrderItem($pdo);
    }

    /**
     * Membuat order dan mengurangi stok secara aman (tidak bisa minus) di dalam transaksi.
     * Jika flash sale aktif, transaksi memakai locking (BEGIN IMMEDIATE) agar penulisan DB terkunci lebih awal.
     *
     * @return array{order: array, items: array<int, array>}
     */
    public function createOrder(array $payload, bool $flashSale = false): array
    {
        $errors = $this->validateOrderPayload($payload);
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $customerName = isset($payload['customer_name']) ? (string) $payload['customer_name'] : null;
        $items = $payload['items'];

        $txMode = $flashSale ? 'immediate' : 'pdo';
        if ($txMode === 'immediate') {
            $this->pdo->exec('BEGIN IMMEDIATE');
        } else {
            $this->pdo->beginTransaction();
        }
        try {
            $orderId = $this->orderModel->create($customerName);

            $total = 0;
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['quantity'];

                $product = $this->productModel->findById($productId);
                if ($product === null) {
                    throw new ValidationException([
                        'items' => 'Produk tidak ditemukan: ' . $productId,
                    ]);
                }

                $ok = $this->productModel->decrementStockIfEnough($productId, $qty);
                if (!$ok) {
                    throw new OutOfStockException($productId, $qty);
                }

                $unitPrice = (int) $product['price'];
                $this->orderItemModel->create($orderId, $productId, $qty, $unitPrice);
                $total += $qty * $unitPrice;
            }

            $this->orderModel->updateTotal($orderId, $total);
            if ($txMode === 'immediate') {
                $this->pdo->exec('COMMIT');
            } else {
                $this->pdo->commit();
            }

            $order = $this->orderModel->findById($orderId);
            $orderItems = $this->orderItemModel->forOrder($orderId);

            return [
                'order' => $order ?? [],
                'items' => $orderItems,
            ];
        } catch (\Throwable $e) {
            if ($txMode === 'immediate') {
                try {
                    $this->pdo->exec('ROLLBACK');
                } catch (\Throwable $ignored) {
                }
            } elseif ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string, string> */
    /**
     * Memvalidasi payload order agar minimal punya 1 item dan kuantitas valid.
     *
     * @return array<string, string>
     */
    private function validateOrderPayload(array $payload): array
    {
        $errors = [];

        if (!isset($payload['items']) || !is_array($payload['items']) || count($payload['items']) < 1) {
            $errors['items'] = 'Order minimal memiliki satu item.';
            return $errors;
        }

        foreach ($payload['items'] as $idx => $item) {
            if (!is_array($item)) {
                $errors['items.' . $idx] = 'Format item tidak valid.';
                continue;
            }
            if (!isset($item['product_id']) || !is_numeric($item['product_id'])) {
                $errors['items.' . $idx . '.product_id'] = 'product_id wajib dan harus angka.';
            }
            if (!isset($item['quantity']) || !is_numeric($item['quantity']) || (int) $item['quantity'] <= 0) {
                $errors['items.' . $idx . '.quantity'] = 'quantity wajib dan harus lebih dari 0.';
            }
        }

        return $errors;
    }
}
