<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SuperAdminAuth
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
            if(Auth::user()->hasRole('super_admin')){
                return $next($request);
            }
        }

        $response = [
            'code' => 404,
            'status' => 'fail',
            'message' => 'access.denied'
        ];
        return response($response, 404);
    }
}
