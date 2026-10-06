<?php

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Applied by the router to every POST request. Accepts the form field or the X-CSRF-Token header. */
class CsrfMiddleware
{
    public function handle(Request $request): ?Response
    {
        $token = $request->input('_token') ?? $request->header('X-CSRF-Token');

        if (!Session::verifyCsrf(is_string($token) ? $token : null)) {
            throw new HttpException(419, 'Your session expired. Please refresh the page and try again.');
        }

        return null;
    }
}
