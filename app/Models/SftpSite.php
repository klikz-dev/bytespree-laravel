<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\OldCrypt;

/**
 * App\Models\SftpSite
 *
 * @property        int                                            $id
 * @property        string|null                                    $hostname
 * @property        string|null                                    $port
 * @property        string|null                                    $username
 * @property        mixed|null                                     $password
 * @property        string|null                                    $default_path
 * @property        bool|null                                      $is_deleted
 * @property        \Illuminate\Support\Carbon|null                $created_at
 * @property        \Illuminate\Support\Carbon|null                $updated_at
 * @property        \Illuminate\Support\Carbon|null                $deleted_at
 * @method   static \Database\Factories\SftpSiteFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite newQuery()
 * @method   static \Illuminate\Database\Query\Builder|SftpSite    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite query()
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereDefaultPath($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereHostname($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite wherePassword($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite wherePort($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SftpSite whereUsername($value)
 * @method   static \Illuminate\Database\Query\Builder|SftpSite    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|SftpSite    withoutTrashed()
 * @mixin \Eloquent
 */
class SftpSite extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_sftp_sites';

    protected $casts = [
        'password' => OldCrypt::class,
    ];
}
