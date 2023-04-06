<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Permission
 *
 * @property        int                                              $id
 * @property        string|null                                      $name
 * @property        bool|null                                        $is_deleted
 * @property        \Illuminate\Support\Carbon|null                  $created_at
 * @property        \Illuminate\Support\Carbon|null                  $updated_at
 * @property        string|null                                      $type
 * @property        string|null                                      $description
 * @property        int|null                                         $product_id
 * @property        \Illuminate\Support\Carbon|null                  $deleted_at
 * @property        \App\Models\Product|null                         $product
 * @method   static \Database\Factories\PermissionFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Permission    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereProductId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Permission whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|Permission    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Permission    withoutTrashed()
 * @mixin \Eloquent
 */
class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'u_permissions';

    protected $with = 'product';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
