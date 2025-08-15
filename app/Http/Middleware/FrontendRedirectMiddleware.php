<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only redirect GET requests to the root path
        if ($request->isMethod('GET') && $request->is('/')) {
            // Check if the request is from a browser (has Accept header with text/html)
            $acceptHeader = $request->header('Accept', '');
            
            if (str_contains($acceptHeader, 'text/html')) {
                $frontendUrl = config('app.frontend_url');
                
                if ($frontendUrl) {
                    return redirect($frontendUrl);
                }
            }
        }

        return $next($request);
    }
}
