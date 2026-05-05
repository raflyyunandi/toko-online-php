<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Request;

final class AdminAuth
{
    /**
     * Memastikan request memiliki admin key yang valid (header: X-Admin-Key).
     */
    public function assertAdmin(Request $request): void
    {
        $expected = (string) Env::get('ADMIN_KEY', '');
        if ($expected === '') {
            throw new UnauthorizedException('ADMIN_KEY belum diset.');
        }

        $provided = '';
        if (isset($request->headers['x-admin-key'])) {
            $provided = (string) $request->headers['x-admin-key'];
        }

        if (!hash_equals($expected, $provided)) {
            throw new UnauthorizedException('Admin key tidak valid.');
        }
    }
}
