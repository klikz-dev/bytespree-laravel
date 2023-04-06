<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ApiKey
 *
 * @property        int                                          $id
 * @property        string                                       $key
 * @property        string                                       $member
 * @property        bool|null                                    $is_admin
 * @property        string|null                                  $email
 * @property        bool|null                                    $is_deleted
 * @property        \Illuminate\Support\Carbon|null              $created_at
 * @property        \Illuminate\Support\Carbon|null              $updated_at
 * @property        \Illuminate\Support\Carbon|null              $deleted_at
 * @method   static \Database\Factories\ApiKeyFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ApiKey    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereEmail($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereIsAdmin($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereKey($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereMember($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ApiKey whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ApiKey    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ApiKey    withoutTrashed()
 * @mixin \Eloquent
 */
class ApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dc_api_keys';
}
