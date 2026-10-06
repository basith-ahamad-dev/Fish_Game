<?php

namespace App\Services;

use RuntimeException;

/** The external Fish API could not be reached or returned something unusable. */
final class FishApiException extends RuntimeException
{
}
