<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class OrderItem
{
    private PDO $pdo;

    /**
     * Inisialisasi model OrderItem untuk akses tabel order_items.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Menambahkan item ke sebuah order (produk, quantity, harga satuan, subtotal).
     */
    public function create(int $orderId, int $productId, int $qty, int $unitPrice): void
    {
        $subtotal = $qty * $unitPrice;
        $stmt = $this->pdo->prepare('
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
            VALUES (:order_id, :product_id, :quantity, :unit_price, :subtotal)
        ');
        $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $productId,
            ':quantity' => $qty,
            ':unit_price' => $unitPrice,
            ':subtotal' => $subtotal,
        ]);
    }

    /**
     * Mengambil daftar item untuk sebuah order berdasarkan order ID.
     */
    public function forOrder(int $orderId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT oi.id, oi.product_id, p.name AS product_name, oi.quantity, oi.unit_price, oi.subtotal
            FROM order_items oi
            INNER JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = :order_id
            ORDER BY oi.id ASC
        ');
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil daftar order yang membeli produk tertentu (dipakai untuk audit stok habis).
     */
    public function ordersForProduct(int $productId, int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $stmt = $this->pdo->prepare('
            SELECT
                o.id AS order_id,
                o.customer_name,
                o.total_amount,
                o.status,
                o.created_at AS order_created_at,
                oi.quantity,
                oi.unit_price,
                oi.subtotal
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE oi.product_id = :product_id
            ORDER BY o.id DESC, oi.id ASC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

