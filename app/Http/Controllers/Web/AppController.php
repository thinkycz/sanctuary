<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AppController
{
    /**
     * Show the application shell (empty state when no collection is selected).
     */
    public function __invoke(): Response
    {
        User::mustAuth();

        return Inertia::render('App/EmptyState');
    }
}
