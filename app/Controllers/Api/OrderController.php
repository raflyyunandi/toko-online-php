<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AdminAuth;
use App\Services\OrderService;
use App\Services\OutOfStockException;
use App\Services\UnauthorizedException;
use App\Services\ValidationException;

final class OrderController
{
    private OrderService $orderService;
    private Order $order;
    private OrderItem $orderItem;
    private AdminAuth $adminAuth;

    /**
     * Inisialisasi controller order beserta dependency yang dibutuhkan.
     */
    public function __construct(OrderService $orderService, Order $order, OrderItem $orderItem, AdminAuth $adminAuth)
    {
        $this->orderService = $orderService;
        $this->order = $order;
        $this->orderItem = $orderItem;
        $this->adminAuth = $adminAuth;
    }

    /**
     * Membuat order baru (minimal 1 item) dan mencegah stok menjadi negatif saat flash sale.
     */
    public function store(Request $request): Response
    {
        try {
            $flashSale = false;
            if (isset($request->headers['x-flash-sale'])) {
                $v = strtolower(trim((string) $request->headers['x-flash-sale']));
                $flashSale = in_array($v, ['1', 'true', 'on', 'yes'], true);
            }
            if (!$flashSale && isset($request->query['flash_sale'])) {
                $v = strtolower(trim((string) $request->query['flash_sale']));
                $flashSale = in_array($v, ['1', 'true', 'on', 'yes'], true);
            }

            $result = $this->orderService->createOrder($request->json, $flashSale);
            return Response::json(['data' => $result], 201);
        } catch (ValidationException $e) {
            return Response::json([
                'error' => [
                    'code' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => $e->errors,
                ],
            ], 422);
        } catch (OutOfStockException $e) {
            return Response::json([
                'error' => [
                    'code' => 'out_of_stock',
                    'message' => $e->getMessage(),
                    'details' => [
                        'product_id' => $e->productId,
                        'requested_qty' => $e->requestedQty,
                    ],
                ],
            ], 409);
        }
    }

    /**
     * Menampilkan detail order berdasarkan order ID (termasuk item-itemnya).
     */
    public function show(Request $request): Response
    {
        $id = (int) ($request->params['id'] ?? 0);
        if ($id <= 0) {
            return Response::error('ID order tidak valid.', 422, 'validation_error');
        }

        $order = $this->order->findById($id);
        if ($order === null) {
            return Response::error('Order tidak ditemukan.', 404, 'not_found');
        }

        $items = $this->orderItem->forOrder($id);
        return Response::json(['data' => ['order' => $order, 'items' => $items]], 200);
    }

    /**
     * Menampilkan daftar order.
     * - User: wajib mengisi query `customer_name` untuk melihat order miliknya.
     * - Admin: bisa melihat semua order tanpa filter (wajib `X-Admin-Key`).
     */
    public function index(Request $request): Response
    {
        $customerName = isset($request->query['customer_name']) ? trim((string) $request->query['customer_name']) : null;
        $limit = isset($request->query['limit']) && is_numeric($request->query['limit']) ? (int) $request->query['limit'] : 50;
        $offset = isset($request->query['offset']) && is_numeric($request->query['offset']) ? (int) $request->query['offset'] : 0;

        if ($customerName === null || $customerName === '') {
            try {
                $this->adminAuth->assertAdmin($request);
            } catch (UnauthorizedException $e) {
                return Response::json([
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'Untuk melihat semua order, butuh admin key. Atau isi query customer_name untuk melihat order milik customer.',
                        'details' => (object) [],
                    ],
                ], 401);
            }
        }

        $orders = $this->order->list($customerName, $limit, $offset);
        $data = [];
        foreach ($orders as $o) {
            $id = (int) $o['id'];
            $data[] = [
                'order' => $o,
                'items' => $this->orderItem->forOrder($id),
            ];
        }

        return Response::json(['data' => $data], 200);
    }

    /**
     * Menampilkan daftar customer yang pernah melakukan order (admin-only).
     */
    public function customers(Request $request): Response
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

        $limit = isset($request->query['limit']) && is_numeric($request->query['limit']) ? (int) $request->query['limit'] : 100;
        $rows = $this->order->listCustomers($limit);
        return Response::json(['data' => $rows], 200);
    }
}
