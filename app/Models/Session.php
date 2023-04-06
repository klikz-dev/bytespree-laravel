<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

/**
 * App\Models\Session
 *
 * @property        int                                           $id
 * @property        int|null                                      $session
 * @property        int|null                                      $user_id
 * @property        bool|null                                     $is_deleted
 * @property        \Illuminate\Support\Carbon|null               $created_at
 * @property        \Illuminate\Support\Carbon|null               $updated_at
 * @property        \Illuminate\Support\Carbon|null               $deleted_at
 * @property        \App\Models\User|null                         $user
 * @method   static \Database\Factories\SessionFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|Session isLive()
 * @method   static \Illuminate\Database\Eloquent\Builder|Session newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Session newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Session    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Session query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Session whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Session whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Session whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Session whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Session whereSession($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Session whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Session whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|Session    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Session    withoutTrashed()
 * @mixin \Eloquent
 */
class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'u_sessions';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeIsLive($query)
    {
        return $query->where('created_at', '>=', now()->subMinutes(6000));
    }

    /**
     * Initialize our session. 
     * 
     * @throws Exception If matching user is not found
     * @throws Exception If user is not found in Orchestration
     */
    public function initialize(): void
    {
        if (empty($this->user)) {
            throw new Exception('Matching user not found for session.');
        }

        $orchestration_user = app('orchestration')->getUser($this->user->user_handle);

        if (! $orchestration_user) {
            throw new Exception('User not found in Orchestration.'); // todo improve
        }

        // Update team user data if needed
        $data = [];
        
        if ($orchestration_user['first_name'] != $this->user->first_name || $orchestration_user['last_name'] != $this->user->last_name) {
            $data = [
                'name'       => trim($orchestration_user['first_name'] . ' ' . $orchestration_user['last_name']),
                'first_name' => $orchestration_user['first_name'],
                'last_name'  => $orchestration_user['last_name'],
            ];
        }

        if ($orchestration_user['email_address'] != $this->user->email) {
            $data['email'] = $orchestration_user['email_address'];
        }

        if (! empty($data)) {
            $this->user->update($data);
        }

        // Initialize session data
        sort($orchestration_user['teams']);

        $user_hash = hash_hmac(
            'sha256', // hash function
            $this->user->user_handle, // user's handle
            config('services.intercom.secret'), // secret key (keep safe!)
        );

        $data = [
            'dfa_preference'   => $orchestration_user['dfa_preference'],
            'email'            => $this->user->email,
            'first_name'       => $orchestration_user['first_name'],
            'is_orch_admin'    => $orchestration_user['is_admin'] === TRUE,
            'last_name'        => $orchestration_user['last_name'],
            'phone'            => $orchestration_user['mobile_number'],
            'session_id'       => $this->session,
            'sso_provider_id'  => $orchestration_user['sso_provider_id'],
            'team'             => app('environment')->getTeam(),
            'team_preference'  => $orchestration_user['team_preference'],
            'teams'            => $orchestration_user['teams'],
            'orchestration_id' => $orchestration_user['id'],
            'user_created_at'  => $this->user->created_at,
            'user_full_name'   => $this->user->name,
            'user_id'          => $this->user->id,
            'username'         => $this->user->user_handle,
            'user_hash'        => $user_hash,
            // TODO: Remove these if not needed, and I don't think we do
            'is_admin'        => $this->user->is_admin,
            'datalake_access' => TRUE,
            'studio_access'   => TRUE,
        ];

        session()->put($data);

        auth()->loginUsingId($this->user->id, FALSE);
    }
}
