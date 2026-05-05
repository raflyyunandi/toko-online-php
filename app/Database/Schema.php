<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Schema
{
    /**
     * Memastikan tabel dan index database sudah tersedia (migrasi otomatis untuk SQLite).
     */
    public static function ensure(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price INTEGER NOT NULL DEFAULT 0,
                stock INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
            );
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_name TEXT NULL,
                total_amount INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT \'created\',
                created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
            );
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price INTEGER NOT NULL,
                subtotal INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
            );
        ');

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id);');
    }
}
