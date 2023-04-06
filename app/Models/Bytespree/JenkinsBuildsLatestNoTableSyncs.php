<?php

namespace App\Models\Bytespree;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Bytespree\JenkinsBuildsLatestNoTableSyncs
 *
 * @method static \Database\Factories\Bytespree\JenkinsBuildsLatestNoTableSyncsFactory  factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|JenkinsBuildsLatestNoTableSyncs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JenkinsBuildsLatestNoTableSyncs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JenkinsBuildsLatestNoTableSyncs query()
 * @mixin \Eloquent
 */
class JenkinsBuildsLatestNoTableSyncs extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = '__bytespree.v_dw_jenkins_builds__latest_no_table_syncs_by_database';
}
