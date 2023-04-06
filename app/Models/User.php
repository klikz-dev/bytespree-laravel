<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Explorer\Project;
use App\Models\Product;
use Auth;
use DB;
use Cache;

/**
 * App\Models\User
 *
 * @property        int                                                                                                       $id
 * @property        string                                                                                                    $user_handle
 * @property        bool|null                                                                                                 $is_admin
 * @property        string|null                                                                                               $email
 * @property        string|null                                                                                               $name
 * @property        bool|null                                                                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                                           $created_at
 * @property        \Illuminate\Support\Carbon|null                                                                           $updated_at
 * @property        bool|null                                                                                                 $can_create_db
 * @property        string|null                                                                                               $first_name
 * @property        string|null                                                                                               $last_name
 * @property        bool|null                                                                                                 $send_database_job_failure_email
 * @property        bool|null                                                                                                 $is_pending
 * @property        \Illuminate\Support\Carbon|null                                                                           $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\UserActivityLog[]                                    $activity_log
 * @property        int|null                                                                                                  $activity_log_count
 * @property        \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property        int|null                                                                                                  $notifications_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Role[]                                               $roles
 * @property        int|null                                                                                                  $roles_count
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                activeIn(\App\Models\Explorer\Project $project, string $schema, string $table, int $in_last_minutes = 5)
 * @method   static \Database\Factories\UserFactory                                                                           factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                isAdmin()
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                newQuery()
 * @method   static \Illuminate\Database\Query\Builder|User                                                                   onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                query()
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereCanCreateDb($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereEmail($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereFirstName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereIsAdmin($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereIsPending($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereLastName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereSendDatabaseJobFailureEmail($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|User                                                                whereUserHandle($value)
 * @method   static \Illuminate\Database\Query\Builder|User                                                                   withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|User                                                                   withoutTrashed()
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    protected $table = 'u_users';

    /**
     * Current user's roles
     */
    public function roles()
    {
        return $this->hasManyThrough(
            Role::class,
            UserRole::class,
            'user_id',
            'id',
            'id',
            'role_id'
        );
    }

    public function projects()
    {
        // todo: see if this needs datalake IDs
        $ids = Product::whereIn('name', ['studio', 'datalake'])->pluck('id');

        return DB::table('u_user_roles')
            ->select(['user_id', 'product_id', 'product_child_id', 'role_id'])
            ->join('u_roles', function ($join) use ($ids) {
                $join->on('u_user_roles.role_id', '=', 'u_roles.id')
                    ->whereIn('u_roles.product_id', $ids);
            })
            ->where('u_user_roles.user_id', $this->id)
            ->whereNull('u_roles.deleted_at')
            ->whereNull('u_user_roles.deleted_at')
            ->get();
    }

    public function studioProjects()
    {
        $ids = Product::where('name', 'studio')->pluck('id');

        return DB::table('u_user_roles')
            ->select(['user_id', 'product_id', 'product_child_id', 'role_id'])
            ->join('u_roles', function ($join) use ($ids) {
                $join->on('u_user_roles.role_id', '=', 'u_roles.id')
                    ->whereIn('u_roles.product_id', $ids);
            })
            ->where('u_user_roles.user_id', $this->id)
            ->whereNull('u_roles.deleted_at')
            ->whereNull('u_user_roles.deleted_at')
            ->get();
    }

    public function databases()
    {
        $datalake = Product::where('name', 'datalake')->first();

        return DB::table('u_user_roles')
            ->select(['user_id', 'product_id', 'product_child_id', 'role_id'])
            ->join('u_roles', function ($join) use ($datalake) {
                $join->on('u_user_roles.role_id', '=', 'u_roles.id')
                    ->where('u_roles.product_id', '=', $datalake->id);
            })
            ->where('u_user_roles.user_id', $this->id)
            ->whereNull('u_roles.deleted_at')
            ->whereNull('u_user_roles.deleted_at')
            ->get();
    }

    public static function handle($handle)
    {
        return self::where('user_handle', $handle)->first();
    }

    public function getAllPermissions(string $product_name, int $product_child_id = NULL)
    {
        return DB::table('u_user_roles')
            ->select('u_user_roles.product_child_id', DB::raw('json_agg(u_permissions.name) as name'), DB::raw('products.name as product_name'))
            ->where('u_user_roles.user_id', $this->id)
            ->whereNull('u_user_roles.deleted_at')
            ->when(! is_null($product_child_id), function ($query) use ($product_child_id) {
                return $query->where('u_user_roles.product_child_id', $product_child_id);
            })
            ->join('products', function ($join) use ($product_name) {
                $join->where('products.name', '=', $product_name)
                    ->whereNull('products.deleted_at');
            })
            ->join('u_roles', function ($join) {
                $join->on('u_roles.id', '=', 'u_user_roles.role_id')
                    ->on('u_roles.product_id', '=', 'products.id')
                    ->whereNull('u_roles.deleted_at');
            })
            ->join('u_role_permissions', function ($join) {
                $join->on('u_role_permissions.role_id', '=', 'u_roles.id')
                    ->whereNull('u_role_permissions.deleted_at');
            })
            ->join('u_permissions', function ($join) {
                $join->on('u_permissions.id', '=', 'u_role_permissions.permission_id')
                    ->whereNull('u_permissions.deleted_at');
            })
            ->groupBy(['u_user_roles.product_child_id', 'products.name'])
            ->orderBy('u_user_roles.product_child_id')
            ->get()
            ->map(function ($item) {
                return [
                    'product_child_id' => $item->product_child_id,
                    'name'             => json_decode($item->name),
                    'product_name'     => $item->product_name
                ];
            });
    }

    public function getAllUserPermissions()
    {
        $permissions = DB::table('u_user_permissions as uup')
            ->select('uup.user_id', DB::raw('json_agg(up.name) as name'))
            ->join('u_permissions as up', 'uup.permission_id', '=', 'up.id')
            ->where('uup.user_id', $this->id)
            ->where('up.type', 'user')
            ->whereNull('uup.deleted_at')
            ->whereNull('up.deleted_at')
            ->groupBy('uup.user_id')
            ->get();

        if (count($permissions) > 0) {
            return [
                'name'    => json_decode($permissions[0]->name),
                'user_id' => $permissions[0]->user_id
            ];
        }

        return [
            'name'    => [],
            'user_id' => NULL
        ];
    }

    public function setAdmin(bool $is_admin = FALSE)
    {
        $this->update(['is_admin' => $is_admin]);

        if ($is_admin) {
            UserRole::where('user_id', $this->id)->delete();
            UserPermission::where('user_id', $this->id)->delete();
        }
    }

    public function clearPermissions()
    {
        UserPermission::where('user_id', $this->id)
            ->delete();
    }

    public function addPermission(int $permission_id)
    {
        UserPermission::updateOrCreate([
            'user_id'       => $this->id,
            'permission_id' => $permission_id,
        ]);
    }

    public function scopeIsAdmin($query)
    {
        return $query->where('is_admin', TRUE);
    }

    public function assignRole(Role $role, int $product_child_id)
    {
        UserRole::updateOrCreate([
            'user_id'          => $this->id,
            'role_id'          => $role->id,
            'product_child_id' => $product_child_id,
        ]);
    }

    // todo: I really don't like output_error here. Output belongs in controllers or middleware.
    public function hasPermissionTo(string $permission, int $product_child_id = NULL, string $product = 'studio', bool $output_error = FALSE)
    {
        $user_id = $this->id;
        $cache_key = 'user:' . $user_id . ':permission:' . $permission . ':product_child_id:' . $product_child_id . ':product:' . $product . ':output_error:' . (string) $output_error;

        return Cache::store('pageload')->rememberForever($cache_key, function () use ($permission, $product_child_id, $product, $output_error) {
            // See if product is disabled only if not an internal user
            if (! $this->isInternalUser()) {
                $exists = Cache::store('pageload')->rememberForever("product:{$product}:model", function () use ($product) {
                    return Product::where('name', $product)->where('is_enabled', TRUE)->first();
                });

                if (! $exists) {
                    if ($output_error) {
                        response()->error('', ['error' => 'Permission denied.'], 403)->throwResponse();
                    }

                    return FALSE;
                }
            }

            // Admin can do everything, so long as a product is enabled. Don't waste logic
            if ($this->is_admin) {
                return TRUE;
            }

            // Do a basic check on user permissions (no child id)
            if (is_null($product_child_id)) {
                $product_model = Cache::store('pageload')->rememberForever("product:{$product}:model", function () use ($product) {
                    return Product::where('name', $product)->first();
                });

                $result = UserPermission::where('user_id', $this->id)
                    ->join('u_permissions as up', 'up.id', 'u_user_permissions.permission_id')
                    ->where('up.name', $permission)
                    ->where('up.product_id', $product_model->id)
                    ->whereNull('u_user_permissions.deleted_at')
                    ->whereNull('up.deleted_at')
                    ->exists();

                if (! $result && $output_error) {
                    response()->error('', ['error' => 'Permission denied.'], 403)->throwResponse();
                }

                return $result;
            }

            if ($permission == 'studio_access') {
                $user_projects = $this->studioProjects()->pluck('product_child_id')->toArray();
                if (in_array($product_child_id, $user_projects)) {
                    return TRUE;
                }
            }

            $result = UserRole::where('user_id', $this->id)
                ->join('u_roles as ur', 'ur.id', 'u_user_roles.role_id')
                ->join('u_role_permissions as urp', 'urp.role_id', 'ur.id')
                ->join('u_permissions as up', 'up.id', 'urp.permission_id')
                ->when($permission != '*', function ($query) use ($permission) {
                    $query->where('up.name', $permission);
                })
                ->where('up.product_id', Product::where('name', $product)->first()->id)
                ->where(function ($query) use ($product_child_id) {
                    $query->where('u_user_roles.product_child_id', $product_child_id);
                })
                ->whereNull('u_user_roles.deleted_at')
                ->whereNull('ur.deleted_at')
                ->whereNull('urp.deleted_at')
                ->whereNull('up.deleted_at')
                ->exists();

            if (! $result && $output_error) {
                response()->error('', ['error' => 'Permission denied.'], 403)->throwResponse();
            }

            return $result;
        });
    }

    public function isInternalUser()
    {
        if (strpos($this->email, '@data-management.com') || strpos($this->email, '@rkdgroup.com')) {
            return TRUE;
        }

        return FALSE;
    }

    // Straight copy/paste. Don't like this, but it's the only way to get the same functionality as the old code without spending a ton of time on it.
    public static function getUsersByProductNameAndChildId(string $product_name, int $product_child_id)
    {
        $args = [$product_name, $product_child_id];
        $sql = <<<SQL
            select 
                user_handle,
                id,
                null as product_id,
                null as role_id,
                null as product_child_id,
                is_admin,
                name,
                first_name,
                last_name
            from u_users where is_admin = true and is_deleted = false
            union
            select
                u_users.user_handle,
                u_users.id,
                u_roles.product_id,
                u_user_roles.role_id,
                u_user_roles.product_child_id,
                u_users.is_admin,
                u_users.name,
                u_users.first_name,
                u_users.last_name
            from
                u_users
            left join u_user_roles on
                u_users.id = u_user_roles.user_id
                and u_user_roles.is_deleted = false
            left join u_roles on
                u_roles.id = u_user_roles.role_id
                and u_roles.is_deleted = false
            where
                u_roles.product_id in (select id from products where name = ?)
                and u_user_roles.product_child_id = ?
                and u_users.is_deleted = false
            order by
                user_handle asc;
            SQL;

        return DB::select($sql, $args);
    }

    /**
     * Slight modification of the getUsersByProductNameAndChildIds to support bulk grabbing of users by product child ids.
     * 
     * @return Collection
     */
    public static function getUsersByProductNameAndChildIds(string $product_name, array $product_child_ids = [])
    {
        if (empty($product_child_ids)) {
            return collect([]);
        }

        $args = [$product_name];
        $where_in = implode(',', $product_child_ids);
    
        $sql = <<<SQL
            select 
                user_handle,
                id,
                null as product_id,
                null as role_id,
                null as product_child_id,
                is_admin,
                name,
                first_name,
                last_name
            from u_users where is_admin = true and is_deleted = false
            union
            select
                u_users.user_handle,
                u_users.id,
                u_roles.product_id,
                u_user_roles.role_id,
                u_user_roles.product_child_id,
                u_users.is_admin,
                u_users.name,
                u_users.first_name,
                u_users.last_name
            from
                u_users
            left join u_user_roles on
                u_users.id = u_user_roles.user_id
                and u_user_roles.is_deleted = false
            left join u_roles on
                u_roles.id = u_user_roles.role_id
                and u_roles.is_deleted = false
            where
                u_roles.product_id in (select id from products where name = ?)
                and u_user_roles.product_child_id in ({$where_in})
                and u_users.is_deleted = false
            order by
                user_handle asc;
            SQL;

        return collect(DB::select($sql, $args));
    }

    // don't like this but we can circle back later
    public static function getDashboardData(string $product_name, int $product_child_id)
    {
        $role_ids = Role::with('product')->get()
            ->filter(function ($role) use ($product_name) {
                return $role->product->name == $product_name;
            })
            ->map(function ($role) {
                return $role->id;
            })
            ->toArray();

        $role_ids = implode(',', $role_ids);

        $args = [$product_child_id];
        $sql = <<<SQL
            select
                u_users.user_handle,
                u_users.id,
                u_user_roles.role_id,
                u_user_roles.product_child_id,
                u_users.is_admin,
                u_users.name,
                u_users.first_name,
                u_users.last_name
            from
                u_users
            left join
                u_user_roles 
                on u_users.id = u_user_roles.user_id
                and u_user_roles.role_id in ($role_ids)
                and u_user_roles.product_child_id = ?
                and u_user_roles.deleted_at is null
            where
                u_users.deleted_at is null
            order by
                user_handle asc
            SQL;
            
        return DB::select($sql, $args);
    }

    public static function parseUsersMentioned(string $content = NULL)
    {
        $users_mentioned = [];
        if (strpos(strtoupper($content), "@") !== FALSE) {
            $users = [];
            preg_match_all('/\s*([@][\w_-]+)/', strtolower($content), $users);
            $users = $users[1];
            foreach ($users as $user_handle) {
                $user_handle = trim($user_handle, '@');
                $user = User::where('user_handle', $user_handle)->first();
                if (empty($user['email'])) {
                    $users_mentioned[$user_handle] = NULL;
                } else {
                    $users_mentioned[$user_handle] = $user;
                }
            }
        }

        return $users_mentioned;
    }

    public function scopeActiveIn($query, Project $project, string $schema, string $table, int $in_last_minutes = 5)
    {
        return static::whereHas('activity_log', function ($query) use ($project, $schema, $table, $in_last_minutes) {
            $query->where('project_id', $project->id)
                ->where('schema_name', $schema)
                ->where('table_name', $table)
                ->where('created_at', '>=', now()->subMinutes($in_last_minutes));
        });
    }

    public function activity_log()
    {
        return $this->hasMany(UserActivityLog::class, 'user_handle', 'user_handle');
    }

    public function getGravatar($s = 25, $d = 'mp', $r = 'g')
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($this->email)));
        $url .= "?s=$s&d=$d&r=$r";

        return $url;
    }

    /**
     * Get our current user for rollbar logging
     * 
     * @return array [] if not authenticated
     */
    public static function loggingUser() : array
    {
        if (empty(Auth::user())) {
            return [];
        }

        return [
            'id'       => Auth::user()->id,
            'username' => Auth::user()->user_handle,
            'email'    => Auth::user()->email_address
        ];
    }
}
