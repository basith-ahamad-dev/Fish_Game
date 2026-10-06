<?php

namespace App\Core;

use RuntimeException;

/** Carries the response to send when request validation fails. */
final class ValidationException extends RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct('The given data was invalid.');
    }
}
