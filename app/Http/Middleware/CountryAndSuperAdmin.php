<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CountryAndSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, $level)
    {
        if(Auth::user()){
            $levels = explode('.', (string) $level);
            foreach($levels as $level){
                if(Auth::user()->hasRole($level)){
                    return $next($request);
                }
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
