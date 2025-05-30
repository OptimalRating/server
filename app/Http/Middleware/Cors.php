<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    // public function handle($request, Closure $next)
    // {
    //     return $next($request)->header('Access-Control-Allow-Origin', '*');
    // }
    public function handle($request, Closure $next)
{
    $response = $next($request);

    if (!is_object($response) || !method_exists($response, 'header')) {
        // Log or return a 500 response to catch this issue
        \Log::error('Invalid response from controller or middleware.', [
            'type' => gettype($response),
            'value' => $response,
        ]);

        return response('Invalid response type', 500);
    }

    return $response->header('Access-Control-Allow-Origin', '*');
}

}
