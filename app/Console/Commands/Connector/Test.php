<?php

namespace App\Console\Commands\Connector;

use App\Classes\Database\{Connection, Table};
use App\Models\PartnerIntegration;
use Exception;
use Illuminate\Console\Command;
use ReflectionMethod;

/**
 * Test a connector's database
 */
class Test extends Command
{
    protected $signature = 'connector:test
        {action : The test action to perform}
        {database_id : The numeric database/partner integration ID}
        {--table= : The optional table to test (<fg=magenta>checks all tables if not provided</>)}
        {--summary : Summarizes tables; exclude record listings -- useful for large tables}';
    protected $description = "Test a connector's database. Supports the following actions: <fg=green>duplicates</>";

    protected ?PartnerIntegration $database = NULL;
    protected ?string $normalized_table = NULL;
    protected array $supported_tables = [];
    protected ?string $table = NULL;

    public function handle(): int
    {
        $action = $this->argument('action');

        if (! method_exists($this, $action)) {
            throw new Exception("Invalid action provided");
        }

        if (! (new ReflectionMethod($this, $action))->isPublic()) {
            throw new Exception("Action provided is not a public action.");
        }

        $this->database = PartnerIntegration::find($this->argument('database_id'));

        if (! $this->database) {
            throw new Exception("Database not found");
        }

        if (! empty($this->option('table'))) {
            $this->table = $this->option('table');
            $this->normalized_table = $this->normalizeTableName($this->table);
        }

        $this->{$action}();

        return Command::SUCCESS;
    }

    /**
     * Check a database's tables (or specified table) for duplicates
     */
    public function duplicates(): void
    {
        if (! empty($this->option('table'))) {
            $tables = [$this->normalized_table];
        } else {
            $tables = $this->database->tables->pluck('name');
        }

        foreach ($tables as $table) {
            $this->info("Checking table {$table} for duplicates");

            $this->table = $table;

            $this->normalized_table = $this->normalizeTableName($this->table);

            $record_count = Table::count($this->database, 'public', $this->normalized_table);

            $this->info("Record count: {$record_count}");

            $columns = $this->getNonDmiColumns();

            $results = $this->getDuplicateRecords('public', $this->normalized_table, $columns);

            if (count($results) === 0) {
                $this->info('Duplicates found: 0');

                continue;
            }
            
            $total_duplicates = 0;

            foreach ($results as $result) {
                $total_duplicates += substr_count($result->dmi_ids, ',');
            }

            $this->error("Duplicates found: {$total_duplicates}");
            
            if (! $this->option('summary')) {
                // Convert the results to an array of arrays for the table() method
                $results = array_map(fn ($result) => (array) $result, $results);

                $this->table(['dmi_ids', 'first_instance', 'latest_instance'], $results);
            }
        }
    }

    /**
     * Normalize a table's name based, taking into consideration the supported tables that already exist. Fixes casing.
     */
    private function normalizeTableName(string $table): string
    {
        $normalized_table = $table;
        if (empty($this->supported_tables)) {
            $this->supported_tables = $this->getTables();
        }

        if (! in_array($table, $this->supported_tables)) {
            $normalized_table = strtolower($table);
            if (! in_array($normalized_table, $this->supported_tables)) {
                return $table;
            }
        }

        return $normalized_table;
    }

    /**
     * Run a query against a database's table, retrieving the duplicates
     */
    private function getDuplicateRecords(string $table_schema, string $table_name, array $column_names = [], ?int $limit = NULL): array
    {
        // Quote and implode our columns for use in the row() SQL function
        $column_string = implode(
            ', ',
            array_map(fn ($column) => "\"{$column}\"", $column_names)
        );

        $sql = <<<SQL
            with hashed_values as (
                select
                        dmi_id,
                        dmi_created,
                        md5(row({$column_string})::text) as hashed_value
                    from
                        "{$table_schema}"."{$table_name}"
            )
            select
                string_agg(hashed_values.dmi_id::text, ',' order by dmi_id) as dmi_ids,
                min(dmi_created) as earliest_date,
                max(dmi_created) as latest_date
            from
                hashed_values
            group by
                hashed_value
            having
                count(hashed_value) > 1
            SQL;

        if (! empty($limit)) {
            $sql .= " limit {$limit}";
        }

        return Connection::connect($this->database)
            ->select($sql);
    }

    /**
     * Get all columns that aren't related to Bytespree
     */
    private function getNonDmiColumns(): array
    {
        $excluded = [
            'dmi_id',
            'dmi_created',
            'dmi_created_at',
            'dmi_deleted',
            'dmi_status',
            'dmi_updated_at',
        ];

        return array_filter(
            Table::columnNames($this->database, 'public', $this->normalized_table),
            fn ($column) => ! in_array($column, $excluded)
        );
    }

    /**
     * Get all tables a connector supports and are in our database
     */
    private function getTables(): array
    {
        return collect(Table::list($this->database, ['public']))
            ->pluck('table_name')
            ->toArray();
    }
}
