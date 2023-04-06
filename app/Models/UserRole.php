<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Explorer\Project;

/**
 * App\Models\UserRole
 *
 * @property        int                                            $id
 * @property        int|null                                       $product_child_id
 * @property        int|null                                       $user_id
 * @property        int|null                                       $role_id
 * @property        bool|null                                      $is_deleted
 * @property        \Illuminate\Support\Carbon|null                $created_at
 * @property        \Illuminate\Support\Carbon|null                $updated_at
 * @property        \Illuminate\Support\Carbon|null                $deleted_at
 * @property        \App\Models\PartnerIntegration|null            $database
 * @property        \App\Models\Role|null                          $role
 * @property        \App\Models\User|null                          $user
 * @method   static \Database\Factories\UserRoleFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole newQuery()
 * @method   static \Illuminate\Database\Query\Builder|UserRole    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole query()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereProductChildId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereRoleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserRole whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|UserRole    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|UserRole    withoutTrashed()
 * @mixin \Eloquent
 */
class UserRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['product_child_id', 'user_id', 'role_id'];

    protected $table = 'u_user_roles';

    protected $with = ['user', 'role'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Will only include when the product is data lake
    public function database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'product_child_id');
    }
    
    // Will only include when the product is studio
    public function project()
    {
        return $this->belongsTo(Project::class, 'product_child_id');
    }

    public static function updateUserRoles(int $user_id, array $children = [])
    {
        self::where('user_id', $user_id)->delete();

        $data = array_filter($children, function ($child) {
            return $child['role_id'] != '';
        });

        if (count($data) < 1) {
            return;
        }

        foreach ($data as $child) {
            $child['user_id'] = $user_id;
            self::updateOrCreate([
                'user_id'          => $user_id,
                'product_child_id' => $child['product_child_id'],
                'role_id'          => $child['role_id']
            ]);
        }
    }
}
