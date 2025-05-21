<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CountryAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Auth::user()){
            if(Auth::user()->hasRole('country_admin'))
                return $next($request);
        }

        $response = [
            'code' => 404,
            'status' => 'fail',
            'message' => 'access.denied'
        ];
        return response($response, 404);
    }
}
