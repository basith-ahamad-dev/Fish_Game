<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (AuthService::user()) {
            return null;
        }

        if ($request->wantsJson()) {
            return Response::json(['message' => 'Please sign in to continue.'], 401);
        }

        if ($request->method() === 'GET') {
            Session::set('intended', $request->path());
        }
        Session::flash('toast', ['type' => 'info', 'message' => 'Please sign in to continue.']);

        return Response::redirect('/login');
    }
}
