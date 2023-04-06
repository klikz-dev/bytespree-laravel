<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class AuthController extends Controller
{
    /**
     * Log the current user out and redirect them to Orchestration
     */
    public function logout()
    {
        Auth::logout();

        session()->flush();

        return redirect(config('orchestration.url') . '/auth/logout');
    }

    public function handle(Request $request)
    {
        if ($request->has('redirect_uri')) {
            $redirect_uri = urldecode($request->redirect_uri);

            return redirect($redirect_uri);
        }

        return redirect('/data-lake');
    }
}