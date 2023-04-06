<?php

namespace App\Listeners\Connector;

use App\Events\Connector\RebuildTableStarted;
use App\Classes\Database\View;
use App\Classes\Database\Connection;

class DropObjectDependencies
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(RebuildTableStarted $event)
    {
        // Drop views that depend on the table
        $definitions = View::dropForTable(
            $event->schemaObject->database,
            $event->schemaObject->schema,
            $event->schemaObject->object
        );

        // Terminate connections to the table. View connections are terminated in View::dropForTable
        Connection::terminateProcessesLockingObject(
            $event->schemaObject->database,
            $event->schemaObject->schema,
            $event->schemaObject->object
        );

        $event->schemaObject->metadata = [
            'dependencyDefinitions' => $definitions
        ];
    }
}