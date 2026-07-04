<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Thinkycz\LaravelCore\Support\Resolver;

class DashboardController
{
    /**
     * Redirect to the application shell.
     */
    public function __invoke(): RedirectResponse
    {
        User::mustAuth();

        return Resolver::resolveRedirector()->to('/app');
    }
}
