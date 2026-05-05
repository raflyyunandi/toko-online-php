<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;

final class HomeController
{
    /**
     * Menampilkan halaman frontend sederhana untuk demo (tab User/Admin).
     */
    public function index(Request $request): Response
    {
        $viewPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'home.php';
        $html = is_file($viewPath) ? (string) file_get_contents($viewPath) : '<h1>toko-online-app</h1>';

        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}

