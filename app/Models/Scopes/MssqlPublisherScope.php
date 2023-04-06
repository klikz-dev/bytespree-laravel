<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\Explorer\PublishingDestination;

class MssqlPublisherScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $destination = PublishingDestination::where('class_name', 'Mssql')->first();

        $builder->where('destination_id', $destination->id);
    }
}
