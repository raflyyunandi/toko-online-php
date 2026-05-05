<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Product
{
    private PDO $pdo;

    /**
     * Inisialisasi model Product untuk akses tabel products.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Membuat produk baru.
     */
    public function create(string $name, int $price, int $stock): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO products (name, price, stock) VALUES (:name, :price, :stock)');
        $stmt->execute([
            ':name' => $name,
            ':price' => $price,
            ':stock' => $stock,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    /**
     * Mengambil detail produk berdasarkan ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, price, stock, created_at FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Mengambil daftar semua produk.
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, price, stock, created_at FROM products ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    /**
     * Mengurangi stok jika stok masih cukup (operasi atomik untuk mencegah stok negatif).
     */
    public function decrementStockIfEnough(int $productId, int $qty): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE products
            SET stock = stock - :qty
            WHERE id = :id AND stock >= :qty
        ');
        $stmt->execute([
            ':id' => $productId,
            ':qty' => $qty,
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Mengatur stok produk ke nilai tertentu (admin-only lewat controller).
     */
    public function setStock(int $productId, int $stock): bool
    {
        $stmt = $this->pdo->prepare('UPDATE products SET stock = :stock WHERE id = :id');
        $stmt->execute([
            ':id' => $productId,
            ':stock' => $stock,
        ]);

        return $stmt->rowCount() === 1;
    }
}

