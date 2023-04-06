<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

/**
 * App\Models\Role
 *
 * @property        int                                                         $id
 * @property        string|null                                                 $role_name
 * @property        bool|null                                                   $is_deleted
 * @property        \Illuminate\Support\Carbon|null                             $created_at
 * @property        \Illuminate\Support\Carbon|null                             $updated_at
 * @property        int|null                                                    $product_id
 * @property        \Illuminate\Support\Carbon|null                             $deleted_at
 * @property        \App\Models\Product|null                                    $product
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property        int|null                                                    $users_count
 * @method   static \Database\Factories\RoleFactory                             factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Role                     onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  whereProductId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  whereRoleName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Role                  whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|Role                     withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Role                     withoutTrashed()
 * @mixin \Eloquent
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'u_roles';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($role) {
            UserRole::where('role_id', $role->id)->delete();
            RolePermission::where('role_id', $role->id)->delete();
        });
    }

    /**
     * Current roles users
     */
    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            UserRole::class,
            'role_id',
            'id',
            'id',
            'user_id'
        );
    }

    public function grantPermission(string $permission_name)
    {
        $permission = Permission::where('name', $permission_name)->first();

        if ($permission) {
            RolePermission::updateOrCreate([
                'role_id'       => $this->id,
                'permission_id' => $permission->id,
            ]);
        }
    }

    public static function permissions(int $id)
    {
        return Permission::select('u_permissions.id', 'u_permissions.name', 'u_permissions.product_id', 'u_role_permissions.id as role_perm_id')
            ->where('u_permissions.type', 'role')
            ->whereNull('u_role_permissions.deleted_at')
            ->leftJoin('u_role_permissions', function ($join) use ($id) {
                $join->on('u_permissions.id', '=', 'u_role_permissions.permission_id')
                    ->on('u_role_permissions.role_id', '=', DB::raw($id))
                    ->on('u_role_permissions.deleted_at', 'is', DB::raw('NULL'));
            })
            ->orderBy('u_permissions.id')
            ->get()
            ->map(function ($permission) {
                $permission->has_permission = empty($permission->role_perm_id) ? FALSE : TRUE;

                return $permission;
            });
    } 

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
