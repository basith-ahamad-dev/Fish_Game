<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class GuestMiddleware
{
    public function handle(Request $request): ?Response
    {
        return AuthService::user() ? Response::redirect('/dashboard') : null;
    }
}
