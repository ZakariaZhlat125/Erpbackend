<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Set the audit context for this request
        ActivityLogService::setContext(
            $request->ip(),
            $request->userAgent()
        );

        return $next($request);
    }
}
