<?php

namespace App\Core;

use App\Services\AuthService;

abstract class Controller
{
    public function __construct(protected Request $request)
    {
    }

    protected function view(string $view, array $data = [], string $layout = 'dashboard'): Response
    {
        return Response::html(View::render($view, $data, $layout));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }

    /** Redirect to the previous page when it belongs to this site. */
    protected function back(string $fallback = '/dashboard'): Response
    {
        $referer = (string) $this->request->referer();
        $host = parse_url($referer, PHP_URL_HOST);
        $port = parse_url($referer, PHP_URL_PORT);

        if ($host && $host . ($port ? ":$port" : '') === ($_SERVER['HTTP_HOST'] ?? '')) {
            return Response::redirect($referer);
        }

        return Response::redirect($fallback);
    }

    /**
     * Validate the request; on failure redirect back with errors (or 422 JSON for fetch calls).
     */
    protected function validate(array $rules, array $labels = [], ?string $redirectTo = null): array
    {
        $validator = Validator::make($this->request->all(), $rules, $labels);
        if ($validator->passes()) {
            return $validator->validated();
        }

        throw $this->validationFailed($validator->errors(), $redirectTo);
    }

    protected function validationFailed(array $errors, ?string $redirectTo = null): ValidationException
    {
        if ($this->request->wantsJson()) {
            return new ValidationException(Response::json([
                'message' => reset($errors) ?: 'Please check the form.',
                'errors'  => $errors,
            ], 422));
        }

        $old = $this->request->all();
        unset($old['password'], $old['password_confirmation'], $old['current_password'], $old['_token']);
        Session::flash('errors', $errors);
        Session::flash('old', $old);

        return new ValidationException($redirectTo ? Response::redirect($redirectTo) : $this->back());
    }

    protected function user(): ?array
    {
        return AuthService::user();
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
