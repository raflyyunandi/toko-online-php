<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Order
{
    private PDO $pdo;

    /**
     * Inisialisasi model Order untuk akses tabel orders.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Membuat order baru dan mengembalikan order ID.
     */
    public function create(?string $customerName): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO orders (customer_name, total_amount, status) VALUES (:customer_name, 0, :status)');
        $stmt->execute([
            ':customer_name' => $customerName,
            ':status' => 'created',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Memperbarui total_amount pada order setelah semua item dihitung.
     */
    public function updateTotal(int $orderId, int $totalAmount): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders SET total_amount = :total_amount WHERE id = :id');
        $stmt->execute([
            ':id' => $orderId,
            ':total_amount' => $totalAmount,
        ]);
    }

    /**
     * Mengambil detail order berdasarkan order ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, customer_name, total_amount, status, created_at FROM orders WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Mengambil daftar order (bisa difilter berdasarkan customer_name).
     */
    public function list(?string $customerName, int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        if ($customerName !== null && trim($customerName) !== '') {
            $stmt = $this->pdo->prepare('
                SELECT id, customer_name, total_amount, status, created_at
                FROM orders
                WHERE customer_name = :customer_name
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':customer_name', $customerName, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare('
            SELECT id, customer_name, total_amount, status, created_at
            FROM orders
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mengambil daftar customer yang pernah order beserta ringkasan jumlah order dan total belanja.
     */
    public function listCustomers(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare('
            SELECT customer_name, COUNT(*) AS orders_count, SUM(total_amount) AS total_spent
            FROM orders
            WHERE customer_name IS NOT NULL AND TRIM(customer_name) <> \'\'
            GROUP BY customer_name
            ORDER BY orders_count DESC, total_spent DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
