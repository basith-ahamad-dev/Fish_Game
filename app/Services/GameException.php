<?php

namespace App\Services;

use RuntimeException;

/** A game rule was violated (unknown game, already finished, …). Carries an HTTP status. */
final class GameException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 409)
    {
        parent::__construct($message);
    }
}
