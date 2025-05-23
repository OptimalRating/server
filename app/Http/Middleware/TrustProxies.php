<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;


class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    // protected $headers = Request::HEADER_X_FORWARDED_ALL; //outdated in laravel 9
    protected $headers = SymfonyRequest::HEADER_X_FORWARDED_ALL;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Get the response from the next middleware
        $response = $next($request);

        // Set the COOP and COEP headers for every response
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        return $response;
    }
}

// namespace App\Http\Middleware;

// use Illuminate\Http\Request;
// use Fideloper\Proxy\TrustProxies as Middleware;

// class TrustProxies extends Middleware
// {
//     /**
//      * The trusted proxies for this application.
//      *
//      * @var array|string
//      */
//     protected $proxies;

//     /**
//      * The headers that should be used to detect proxies.
//      *
//      * @var int
//      */
//     protected $headers = Request::HEADER_X_FORWARDED_ALL;
// }
