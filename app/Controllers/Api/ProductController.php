<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\AdminAuth;
use App\Services\UnauthorizedException;

final class ProductController
{
    private Product $product;
    private OrderItem $orderItem;
    private AdminAuth $adminAuth;

    /**
     * Inisialisasi controller produk beserta dependency yang dibutuhkan.
     */
    public function __construct(Product $product, OrderItem $orderItem, AdminAuth $adminAuth)
    {
        $this->product = $product;
        $this->orderItem = $orderItem;
        $this->adminAuth = $adminAuth;
    }

    /**
     * Menampilkan daftar produk.
     */
    public function index(Request $request): Response
    {
        return Response::json(['data' => $this->product->all()], 200);
    }

    /**
     * Menampilkan detail produk berdasarkan product ID.
     */
    public function show(Request $request): Response
    {
        $id = (int) ($request->params['id'] ?? 0);
        if ($id <= 0) {
            return Response::error('ID produk tidak valid.', 422, 'validation_error');
        }

        $product = $this->product->findById($id);
        if ($product === null) {
            return Response::error('Produk tidak ditemukan.', 404, 'not_found');
        }

        return Response::json(['data' => $product], 200);
    }

    /**
     * Membuat produk baru (admin-only).
     */
    public function store(Request $request): Response
    {
        try {
            $this->adminAuth->assertAdmin($request);
        } catch (UnauthorizedException $e) {
            return Response::json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => $e->getMessage(),
                    'details' => (object) [],
                ],
            ], 401);
        }

        $payload = $request->json;
        $errors = [];

        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        if ($name === '') {
            $errors['name'] = 'name wajib.';
        }

        $price = isset($payload['price']) && is_numeric($payload['price']) ? (int) $payload['price'] : null;
        if ($price === null || $price < 0) {
            $errors['price'] = 'price wajib dan tidak boleh negatif.';
        }

        $stock = isset($payload['stock']) && is_numeric($payload['stock']) ? (int) $payload['stock'] : null;
        if ($stock === null || $stock < 0) {
            $errors['stock'] = 'stock wajib dan tidak boleh negatif.';
        }

        if ($errors !== []) {
            return Response::json(['error' => ['code' => 'validation_error', 'message' => 'Validasi gagal.', 'details' => $errors]], 422);
        }

        $product = $this->product->create($name, $price, $stock);
        return Response::json(['data' => $product], 201);
    }

    /**
     * Mengubah stok produk secara langsung (admin-only).
     */
    public function setStock(Request $request): Response
    {
        try {
            $this->adminAuth->assertAdmin($request);
        } catch (UnauthorizedException $e) {
            return Response::json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => $e->getMessage(),
                    'details' => (object) [],
                ],
            ], 401);
        }

        $id = (int) ($request->params['id'] ?? 0);
        if ($id <= 0) {
            return Response::error('ID produk tidak valid.', 422, 'validation_error');
        }

        $payload = $request->json;
        $stock = isset($payload['stock']) && is_numeric($payload['stock']) ? (int) $payload['stock'] : null;
        if ($stock === null || $stock < 0) {
            return Response::json([
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'Validasi gagal.',
                    'details' => ['stock' => 'stock wajib dan tidak boleh negatif.'],
                ],
            ], 422);
        }

        $exists = $this->product->findById($id);
        if ($exists === null) {
            return Response::error('Produk tidak ditemukan.', 404, 'not_found');
        }

        $this->product->setStock($id, $stock);
        $updated = $this->product->findById($id);
        return Response::json(['data' => $updated], 200);
    }

    /**
     * Menampilkan daftar order yang membeli produk tertentu (admin-only).
     */
    public function orders(Request $request): Response
    {
        try {
            $this->adminAuth->assertAdmin($request);
        } catch (UnauthorizedException $e) {
            return Response::json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => $e->getMessage(),
                    'details' => (object) [],
                ],
            ], 401);
        }

        $id = (int) ($request->params['id'] ?? 0);
        if ($id <= 0) {
            return Response::error('ID produk tidak valid.', 422, 'validation_error');
        }

        $product = $this->product->findById($id);
        if ($product === null) {
            return Response::error('Produk tidak ditemukan.', 404, 'not_found');
        }

        $limit = isset($request->query['limit']) && is_numeric($request->query['limit']) ? (int) $request->query['limit'] : 50;
        $offset = isset($request->query['offset']) && is_numeric($request->query['offset']) ? (int) $request->query['offset'] : 0;

        $rows = $this->orderItem->ordersForProduct($id, $limit, $offset);

        return Response::json([
            'data' => [
                'product' => $product,
                'orders' => $rows,
            ],
        ], 200);
    }
}

