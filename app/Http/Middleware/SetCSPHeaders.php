<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCSPHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the response from the next middleware
        $response = $next($request);

        // Set the correct CSP headers to allow Google scripts
        $response->headers->set('Content-Security-Policy', "script-src 'self' https://www.gstatic.com https://apis.google.com; object-src 'none';");

        return $response;
    }
}
