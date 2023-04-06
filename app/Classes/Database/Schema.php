<?php

namespace App\Classes\Database;

use App\Models\PartnerIntegration;
use Exception;

class Schema extends Connection
{
    public function createSchema(PartnerIntegration $database, string $schema)
    {
        return $this->connect($database)
            ->statement("CREATE SCHEMA {$schema};");
    }

    public function dependentViews(PartnerIntegration $database, string $schema)
    {
        $sql = <<<SQL
            SELECT  
            distinct dependent_ns.nspname as view_schema
            , dependent_view.relname as view_name 
            , source_ns.nspname as source_schema
            , source_table.relname as source_table
            FROM pg_depend 
            JOIN pg_rewrite ON pg_depend.objid = pg_rewrite.oid 
            JOIN pg_class as dependent_view ON pg_rewrite.ev_class = dependent_view.oid 
            JOIN pg_class as source_table ON pg_depend.refobjid = source_table.oid 
            JOIN pg_namespace dependent_ns ON dependent_ns.oid = dependent_view.relnamespace
            JOIN pg_namespace source_ns ON source_ns.oid = source_table.relnamespace
            WHERE source_ns.nspname = '$schema'
            SQL;

        return $this->connect($database)
            ->select($sql);
    }

    /**
     * Drop a schema from a database
     */
    public function dropSchema(PartnerIntegration $database, string $schema, bool $cascade = TRUE)
    {
        $sql = "DROP SCHEMA IF EXISTS \"{$schema}\"";

        if ($cascade) {
            $sql .= " CASCADE";
        }

        return $this->connect($database)
            ->statement($sql);
    }
}
