<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Manager\JenkinsBuild;
use App\Models\Session;
use Auth;
use Exception;

class MeController extends Controller
{
    public function me()
    {
        return response()->success(auth()->user());
    }
    
    public function teams()
    {
        return response()->success(session()->get('teams'));
    }

    public function notifications(Request $request)
    {
        return response()->success(
            app('orchestration')->getUserNotifications(session()->get('username'), $request->last_id ?? 0)
        );
    }

    public function stats()
    {
        $notifications = app('orchestration')->getUserNotifications(session()->get('username'), 0)['notifications'];

        return response()->success([
            "jobs_running"  => JenkinsBuild::unfinished()->count(),
            "notifications" => count($notifications)
        ]);
    }

    public function update(Request $request)
    {
        $password_changed = filter_var($request->password_changed, FILTER_VALIDATE_BOOLEAN);
        $dfa_changed = filter_var($request->dfa_changed, FILTER_VALIDATE_BOOLEAN);
        $team_preference_changed = filter_var($request->team_preference_changed, FILTER_VALIDATE_BOOLEAN);
        $name_changed = filter_var($request->name_changed, FILTER_VALIDATE_BOOLEAN);
        $email_changed = filter_var($request->email_changed, FILTER_VALIDATE_BOOLEAN);
        $phone_changed = filter_var($request->phone_changed, FILTER_VALIDATE_BOOLEAN);

        $responses = [];

        if ($password_changed && ! empty($request->password) && empty($request->current_password)) {
            return response()->error("Current password required to change password", status_code: 400);
        }

        if ($password_changed == TRUE) {
            $responses[] = app('orchestration')->changePassword(
                session()->get('username'),
                $request->current_password,
                $request->password
            );
        }

        if ($dfa_changed == TRUE || $name_changed == TRUE || $team_preference_changed) {
            $responses[] = app('orchestration')->updateUser(
                session()->get('username'),
                $request->first_name,
                $request->last_name,
                $request->dfa_preference,
                $request->team_preference
            );
        }

        if ($email_changed == TRUE) {
            $responses[] = app('orchestration')->changeEmail(
                session()->get('username'),
                $request->email
            );
        }

        if ($phone_changed == TRUE) {
            if (empty($request->phone)) {
                $response = app('orchestration')->removePhone(
                    session()->get('username')
                );
            } else {
                $response = app('orchestration')->changePhone(
                    session()->get('username'),
                    $request->phone
                );
            }

            if (! array_key_exists('status', $response) || $response['status'] != 'ok') {
                $response = [
                    'status'  => 'error',
                    'message' => "Phone number could not be updated. Please make sure it's valid with country code included."
                ];
            }

            $responses[] = $response;
        }

        if ($name_changed == TRUE || $dfa_changed == TRUE || $team_preference_changed == TRUE || ($phone_changed == TRUE && empty($phone))) {
            $data = app('orchestration')->refreshSession(
                app('environment')->getTeam(),
                session()->get('username'),
                $request->first_name,
                $request->last_name,
                Auth::user()->email,
            );

            if (is_array($data)) {
                $session = Session::where('session', $data['session'])->first();

                if ($session) {
                    $session->initialize();
                }
            }
        }

        return response()->success($responses, 'User has been updated.');
    }

    public function join(Request $request)
    {
        if (! $request->filled('email_code') && ! $request->filled('invitation_code')) {
            return response()->error('No code provided.', status_code: 400);
        }

        try {
            $response = app('orchestration')->acceptInvitation(Auth::user()->user_handle, $request->invitation_code, $request->email_code);

            if (empty($response)) {
                return response()->error('Unable to join team. Double check invitation code.', status_code: 500);
            }

            if (! empty($response['status']) && $response['status'] == 'error') {
                return response()->error(data: $response['data'], message: $response['message'], status_code: 400);
            }

            $team = app('environment')->getTeam();
            $teams = app('orchestration')->getUserTeams(Auth::user()->user_handle);
            $response = $response;

            return response()->success(compact('team', 'teams', 'response'), 'You have joined the team.');
        } catch (Exception $e) {
            return response()->error($e->getMessage(), status_code: 400);
        }
    }

    public function permissions(Request $request)
    {
        $permissions = match ($request->product) {
            'datalake' => $request->user()->getAllPermissions('datalake', $request->input('product_child_id', NULL)),
            'studio'   => $request->user()->getAllPermissions('studio', $request->input('product_child_id', NULL)),
            NULL       => $request->user()->getAllUserPermissions(),
            default    => NULL,
        };

        return response()->success($permissions);
    }
}
