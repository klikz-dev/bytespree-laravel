<?php

namespace App\Classes\Publishers;

use App\Classes\Database\View as DB_View;

class View extends Publisher
{
    public $notify_users = FALSE; // Should BP_Publish notify users of success or failure?

    public function __construct()
    {
        $this->chunk_type = 'action';
    }

    /**
     * Callback to be executed before our publish job runs. Set our out file path and create our file pointer for writing to it.
     *
     * @return void
     */
    public function beforePublish()
    {
        return TRUE;
    }

    /**
     * Callback for when we retrieve a chunk of data from our outer SQL cursor
     *
     * @return void
     */
    public function chunk()
    {
        DB_View::refresh($this->project->primary_database, $this->project->name, $this->destination_options->name);
    }
}
