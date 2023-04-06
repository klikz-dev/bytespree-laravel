<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectSqlUser
 *
 * @property        int                                                  $id
 * @property        int|null                                             $data_warehouse_database_id
 * @property        string|null                                          $name
 * @property        string|null                                          $password
 * @property        string|null                                          $created_by
 * @property        string|null                                          $email_notificants
 * @property        mixed|null                                           $ip_addresses
 * @property        bool|null                                            $is_active
 * @property        bool|null                                            $is_deleted
 * @property        \Illuminate\Support\Carbon|null                      $created_at
 * @property        \Illuminate\Support\Carbon|null                      $updated_at
 * @property        \Illuminate\Support\Carbon|null                      $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSqlUser    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereCreatedBy($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereDataWarehouseDatabaseId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereEmailNotificants($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereIpAddresses($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereIsActive($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser wherePassword($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSqlUser whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectSqlUser    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSqlUser    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectSqlUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_sql_users';
}
