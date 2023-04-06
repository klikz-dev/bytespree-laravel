<?php
namespace App\Classes\Database;

use App\Models\PartnerIntegration;

class SchemaObject
{
    /**
     * __construct
     *
     * @param PartnerIntegration $database Database instance
     * @param string             $schema   Database schema that owns object          
     * @param string             $object   Database object  (table, view, etc.)  
     * @param  string   (optional) $object_type Database object type. Used to distinguish between tables, views, materialized views, etc.
     * @param  array    (optional) $metadata    Additional metadata for the object
     * @return void
     */
    public function __construct(
        public PartnerIntegration $database,
        public string $schema,
        public string $object,
        public string $object_type = 'table',
        public array $metadata = [],
    ) {
    }
}