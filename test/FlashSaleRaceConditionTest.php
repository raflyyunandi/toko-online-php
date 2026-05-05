<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class FlashSaleRaceConditionTest extends TestCase
{
    public function test_flash_sale_mencegah_stok_negatif_saat_order_bersamaan(): void
    {
        $basePath = dirname(__DIR__);
        $port = 8007;
        $baseUrl = "http://127.0.0.1:$port";

        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'toko-online-app-test';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        $dbPath = $tmpDir . DIRECTORY_SEPARATOR . 'race.sqlite';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $adminKey = 'test-admin-key';
        $env = array_merge($_ENV, [
            'APP_ENV' => 'test',
            'DB_PATH' => $dbPath,
            'ADMIN_KEY' => $adminKey,
        ]);

        $server = $this->startServer($basePath, $port, $env);
        try {
            $this->waitForHealth($baseUrl);

            $createProduct = $this->httpJson('POST', $baseUrl . '/api/products', [
                'name' => 'Flash Sale Item',
                'price' => 10000,
                'stock' => 10,
            ], [
                'X-Admin-Key' => $adminKey,
            ]);
            $this->assertSame(201, $createProduct['status'], (string) $createProduct['body']);

            $productId = (int) ($createProduct['json']['data']['id'] ?? 0);
            $this->assertGreaterThan(0, $productId);

            $workers = [];
            $workerCount = 20;
            for ($i = 1; $i <= $workerCount; $i++) {
                $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($basePath . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'order_worker.php')
                    . ' ' . escapeshellarg($baseUrl)
                    . ' ' . escapeshellarg((string) $productId)
                    . ' ' . escapeshellarg('1')
                    . ' ' . escapeshellarg('cust-' . $i);

                $spec = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];

                $proc = proc_open($cmd, $spec, $pipes, $basePath, $env);
                $this->assertIsResource($proc);
                $workers[] = ['proc' => $proc, 'pipes' => $pipes];
            }

            $success = 0;
            $conflict = 0;
            $other = 0;

            foreach ($workers as $w) {
                fclose($w['pipes'][0]);
                $stdout = stream_get_contents($w['pipes'][1]);
                $stderr = stream_get_contents($w['pipes'][2]);
                fclose($w['pipes'][1]);
                fclose($w['pipes'][2]);

                $exitCode = proc_close($w['proc']);
                $this->assertSame(0, $exitCode, $stderr);

                $decoded = json_decode((string) $stdout, true);
                $status = is_array($decoded) ? (int) ($decoded['status'] ?? 0) : 0;

                if ($status === 201) {
                    $success++;
                } elseif ($status === 409) {
                    $conflict++;
                } else {
                    $other++;
                }
            }

            $this->assertSame(10, $success, "Ekspektasi 10 order sukses. success=$success conflict=$conflict other=$other");
            $this->assertSame(10, $conflict, "Ekspektasi 10 order ditolak (stok habis). success=$success conflict=$conflict other=$other");
            $this->assertSame(0, $other, "Tidak boleh ada status lain. success=$success conflict=$conflict other=$other");

            $product = $this->httpJson('GET', $baseUrl . '/api/products/' . $productId);
            $this->assertSame(200, $product['status'], (string) $product['body']);

            $stock = (int) ($product['json']['data']['stock'] ?? -999);
            $this->assertSame(0, $stock);
            $this->assertGreaterThanOrEqual(0, $stock);
        } finally {
            $this->stopServer($server);
        }
    }

    /** @return array{proc: resource, pipes: array<int, resource>} */
    private function startServer(string $basePath, int $port, array $env): array
    {
        $cmd = escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg($basePath . DIRECTORY_SEPARATOR . 'public') . ' ' . escapeshellarg($basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php');

        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $spec, $pipes, $basePath, $env);
        $this->assertIsResource($proc);

        return ['proc' => $proc, 'pipes' => $pipes];
    }

    private function stopServer(array $server): void
    {
        foreach ($server['pipes'] as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($server['proc'])) {
            proc_terminate($server['proc']);
            proc_close($server['proc']);
        }
    }

    private function waitForHealth(string $baseUrl): void
    {
        $start = microtime(true);
        do {
            $res = $this->httpJson('GET', $baseUrl . '/api/health');
            if ($res['status'] === 200) {
                return;
            }
            usleep(100_000);
        } while (microtime(true) - $start < 5);

        $this->fail('Server tidak siap.');
    }

    /** @return array{status:int, body:string, json:array} */
    private function httpJson(string $method, string $url, ?array $payload = null, array $extraHeaders = []): array
    {
        $headers = "Accept: application/json\r\n";
        $content = null;

        if ($payload !== null) {
            $headers .= "Content-Type: application/json\r\n";
            $content = json_encode($payload);
        }

        foreach ($extraHeaders as $k => $v) {
            $headers .= $k . ': ' . $v . "\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => $headers,
                'content' => $content,
                'ignore_errors' => true,
                'timeout' => 10,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        $status = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
                    $status = (int) $m[1];
                    break;
                }
            }
        }

        $json = [];
        $decoded = json_decode((string) $body, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }

        return [
            'status' => $status,
            'body' => (string) $body,
            'json' => $json,
        ];
    }
}
