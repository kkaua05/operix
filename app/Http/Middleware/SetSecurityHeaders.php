<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defense-in-depth response headers (§50) that cost nothing and cover
 * classes of attack CSRF tokens and Blade's auto-escaping don't:
 * clickjacking (framing this app inside a malicious page), MIME-sniffing
 * an upload into executing as something it isn't, and leaking the full
 * referrer URL (which can contain tokens in query strings) to third-party
 * links.
 */
class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
