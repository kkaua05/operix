<?php

namespace App\Http\Middleware;

use App\Support\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the acting tenant from the authenticated user and makes it
 * available to the rest of the request via CurrentCompany. A user with a
 * null company_id (e.g. a SUPER_ADMIN) operates with no tenant restriction.
 */
class EnsureCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        CurrentCompany::set($request->user()?->company_id);

        return $next($request);
    }
}
