<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/** Stub — implemented in Phase 1. */
class LibraryApiController
{
    public function __call(string $name, array $args): Response
    {
        return Response::html('<p>🚧 ' . static::class . '::' . $name . ' — coming in Phase 1.</p>');
    }
}
