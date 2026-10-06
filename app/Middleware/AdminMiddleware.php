<?php

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class AdminMiddleware
{
    public function handle(Request $request): ?Response
    {
        if ((AuthService::user()['role'] ?? null) !== 'admin') {
            throw new HttpException(403, 'You do not have permission to view this page.');
        }

        return null;
    }
}
