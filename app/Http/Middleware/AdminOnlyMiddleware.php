<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class AdminOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $respond_with_json = NULL)
    {
        if (Auth::user()->is_admin !== TRUE) {
            if (filter_var($respond_with_json, FILTER_VALIDATE_BOOLEAN) === TRUE) {
                return response()->error('You do not have permission to access this resource.', [], 403);
            }

            if (request()->isJson() || request()->expectsJson()) {
                return response()->error('You do not have permission to access this resource', [], 403);
            }

            return abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
