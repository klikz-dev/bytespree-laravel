<?php
namespace App\Events\Database;

use App\Models\PartnerIntegration;
use App\Classes\Database\SchemaObject;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SchemaObjectEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
    /**
     * Create a new event instance.
     *
     * @return void
     */    
    public function __construct(public SchemaObject $schemaObject)
    {
    }
}