<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use App\Models\Session;
use Exception;

class OrchestrationAuthentication
{
    /**
     * Check to see if a user is logged in or if they're passing the session parameter from Orchestration.
     * 
     * If any checks fail, redirect to the Orchestration login page.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        $redirect_url = $this->generateRedirectUrl();

        if (! $request->has('session')) {
            return response()->view('redirect', compact('redirect_url'));
        }

        $session = preg_replace('/[^0-9]/', '', $request->session);

        if (empty($session)) {
            return response()->view('redirect', compact('redirect_url'));
        }

        $session = Session::isLive()
            ->where('session', $session)
            ->first();

        if (! $session) {
            return response()->view('redirect', compact('redirect_url'));
        }

        try {
            $session->initialize();

            return $next($request);
        } catch (Exception $e) {
            return response()->view('redirect', compact('redirect_url'));
        }
    }

    /**
     * Generate a redirect URL with Orchestration after compiling it
     */
    public function generateRedirectUrl(): string
    {
        $redirect_url = rtrim(config('app.url'), '/') . '/' . request()->path();

        if (! empty(request()->getQueryString())) {
            $redirect_url .= '?' . request()->getQueryString();
        }

        return app('orchestration')->redirectUrl($redirect_url);
    }
}
