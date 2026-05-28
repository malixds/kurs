<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->companies()->exists()) {
            if ($request->expectsJson()) {
                abort(403, 'Company context is required.');
            }

            return redirect()->route('onboarding.welcome');
        }

        return $next($request);
    }
}
