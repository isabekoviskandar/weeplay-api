<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            if (! $this->isAllowedOrigin($origin)) {
                return new Response('', Response::HTTP_FORBIDDEN);
            }

            $response = new Response('', Response::HTTP_NO_CONTENT);
            $this->addCorsHeaders($response, $origin, $request);

            return $response;
        }

        $response = $next($request);

        // Add CORS headers to actual response
        if ($this->isAllowedOrigin($origin)) {
            $this->addCorsHeaders($response, $origin, $request);
        }

        return $response;
    }

    private function isAllowedOrigin(?string $origin): bool
    {
        return $origin !== null && in_array($origin, config('cors.allowed_origins', []), true);
    }

    private function addCorsHeaders(Response $response, string $origin, Request $request): void
    {
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            $request->header('Access-Control-Request-Headers', 'Content-Type, Authorization, X-Requested-With, Accept'),
        );
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Vary', 'Origin', false);
    }
}
