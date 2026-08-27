<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The landing page: a title, a tag search and a way into the grid. It belongs
 * to no module — it only points at them.
 */
final class HomeController
{
    public function __invoke(): Response
    {
        return Inertia::render('Home');
    }
}
