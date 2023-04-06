<?php

namespace App\Classes\Publishers;

use App\Models\Explorer\ProjectSnapshot;
use App\Classes\Database\Connection;
use App\Classes\Database\Table;
use Exception;

class Snapshot extends Publisher
{
    public $notify_users = FALSE; // Should BP_Publish notify users of success or failure?

    public function __construct()
    {
        $this->chunk_type = 'sql';
    }

    /**
     * Callback to be executed before our publish job runs. Set our out file path and create our file pointer for writing to it.
     *
     * @return void
     */
    public function beforePublish()
    {
        if (empty($this->destination_options->name)) {
            throw new Exception('Snapshot name was not found.');
        }

        $append_timestamp = filter_var($this->destination_options->append_timestamp ?? FALSE, FILTER_VALIDATE_BOOLEAN);

        if ($append_timestamp) {
            $this->destination_options->name .= '_' . date('Ymdhis');
        }

        echo "Looking for table {$this->destination_options->name}\n\n";
        if (Table::exists($this->project->primary_database, $this->project->name, $this->destination_options->name)) {
            $snapshot = ProjectSnapshot::where('project_id', $this->project->id)
                ->where('name', $this->destination_options->name)
                ->first();

            if (empty($snapshot)) {
                throw new Exception("Snapshot publishing failed because a non-snapshot table exists with the same name ({$this->destination_options->name}) in the {$this->project->name} schema.");
            }

            echo "Table {$this->destination_options->name} appears to be a snapshot. Dropping table.\n\n";

            Table::drop($this->project->primary_database, $this->project->name, $this->destination_options->name);

            echo "Deleting db record\n\n";

            $snapshot->delete();
        }
    }

    /**
     * Callback for when we retrieve a chunk of data from our outer SQL cursor
     *
     * @param  string $sql The SQL returned from the destination options
     * @return void
     */
    public function chunk(string $sql)
    {
        echo "Creating {$this->destination_options->name}\n\n";
        $sql = <<<SQL
            CREATE TABLE "{$this->project->name}"."{$this->destination_options->name}" AS {$sql}
            SQL;

        $result = Connection::connect($this->project->primary_database)->statement($sql);

        if ($result) {
            ProjectSnapshot::create([
                'project_id'    => $this->project->id,
                'user_id'       => $this->username,
                'name'          => $this->destination_options->name,
                'description'   => $this->destination_options->description ?? '',
                'source_table'  => $this->source_table,
                'source_schema' => $this->source_schema
            ]);
        } else {
            throw new Exception('Snapshot failed to create.');
        }
    }
}
