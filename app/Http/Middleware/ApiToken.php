<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// https://www.blitter.se/utils/basic-authentication-header-generator/

class ApiToken
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
        if (strpos($request->header('Authorization'), env('API_TOKEN')) == false) {
            return response()->json('Unauthorized', 401);
        }
        return $next($request);
    }
}
