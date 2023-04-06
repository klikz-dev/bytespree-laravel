<?php

namespace App\Listeners\Connector;

use App\Events\Connector\RebuildTable;
use App\Classes\Database\View;

class RestoreObjectDependencies
{
    /**
     * Handle the event.
     *
     * @param  RebuildTableStarted $event
     * @return void
     */
    public function handle(RebuildTable $event)
    {
        $definitions = [];
        if (isset($event->schemaObject->metadata['dependencyDefinitions'])) {
            $definitions = $event->schemaObject->metadata['dependencyDefinitions'];
        }
        if (! empty($definitions) && is_array($definitions)) {
            View::createFromDefinitions($event->schemaObject->database, $definitions);
        }
    }
}