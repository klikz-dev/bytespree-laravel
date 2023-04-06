<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Explorer\PublishingDestination;

return new class extends Migration
{
    public function up()
    {
        PublishingDestination::where('class_name', 'Publishers/MssqlPublishersModel')->update(['class_name' => 'Mssql']);
        PublishingDestination::where('class_name', 'Publishers/CsvPublishersModel')->update(['class_name' => 'Csv']);
        PublishingDestination::where('class_name', 'Publishers/SnapshotPublishersModel')->update(['class_name' => 'Snapshot']);
        PublishingDestination::where('class_name', 'Publishers/SftpPublishersModel')->update(['class_name' => 'Sftp']);
    }

    public function down()
    {
        PublishingDestination::where('class_name', 'Mssql')->update(['class_name' => 'Publishers/MssqlPublishersModel']);
        PublishingDestination::where('class_name', 'Csv')->update(['class_name' => 'Publishers/CsvPublishersModel']);
        PublishingDestination::where('class_name', 'Snapshot')->update(['class_name' => 'Publishers/SnapshotPublishersModel']);
        PublishingDestination::where('class_name', 'Sftp')->update(['class_name' => 'Publishers/SftpPublishersModel']);
    }
};
