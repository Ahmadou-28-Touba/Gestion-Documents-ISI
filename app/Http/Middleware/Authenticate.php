<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests in a SPA, do not redirect to a web login route.
        // Returning null ensures a 401 JSON response instead of a redirect to an undefined 'login' route.
        return null;
    }
}
