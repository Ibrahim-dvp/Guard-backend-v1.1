<?php

if (!function_exists('frontend_url')) {
    /**
     * Generate a URL to the frontend application.
     *
     * @param string $path
     * @return string
     */
    function frontend_url(string $path = ''): string
    {
        $baseUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://https://guard-alpha.vercel.app'));
        
        if (empty($path)) {
            return $baseUrl;
        }
        
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('is_frontend_request')) {
    /**
     * Check if the request is coming from the frontend application.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    function is_frontend_request(\Illuminate\Http\Request $request = null): bool
    {
        $request = $request ?: request();
        $referer = $request->header('referer');
        $origin = $request->header('origin');
        $frontendUrl = config('app.frontend_url');
        
        if (!$frontendUrl) {
            return false;
        }
        
        $frontendHost = parse_url($frontendUrl, PHP_URL_HOST);
        
        if ($referer && str_contains($referer, $frontendHost)) {
            return true;
        }
        
        if ($origin && str_contains($origin, $frontendHost)) {
            return true;
        }
        
        return false;
    }
}
