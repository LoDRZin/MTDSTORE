<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
        
        $request->headers->set('X-Correlation-ID', $traceId);
        \Illuminate\Support\Facades\Log::withContext(['trace_id' => $traceId]);

        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('X-Correlation-ID', $traceId);
        }

        return $response;
    }
}
