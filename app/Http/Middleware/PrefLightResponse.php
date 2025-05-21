<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrefLightResponse
{

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request)
            ->header("Access-Control-Allow-Origin", "*")
            ->header("Access-Control-Allow-Methods", "POST, GET, PUT, OPTIONS, PATCH, DELETE")
            ->header("Access-Control-Allow-Headers", "Accept, Authorization, content-type, token, country, x-request-with, X-Requested-With, Application");
    }
}
