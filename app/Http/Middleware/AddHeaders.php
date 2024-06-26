<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddHeaders
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('connectors/*/logo') || $request->is('admin/connectors/*/orchestration-logo')) {
            return $next($request);
        }

        $response = $next($request);

        return $response->header('Cache-Control', 'nocache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }
}