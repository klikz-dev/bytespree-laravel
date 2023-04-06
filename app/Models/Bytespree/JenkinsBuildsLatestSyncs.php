<?php

namespace App\Models\Bytespree;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Bytespree\JenkinsBuildsLatestSyncs
 *
 * @method static \Database\Factories\Bytespree\JenkinsBuildsLatestSyncsFactory  factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|JenkinsBuildsLatestSyncs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JenkinsBuildsLatestSyncs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JenkinsBuildsLatestSyncs query()
 * @mixin \Eloquent
 */
class JenkinsBuildsLatestSyncs extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = '__bytespree.v_dw_jenkins_builds__latest_syncs_by_database';
}
