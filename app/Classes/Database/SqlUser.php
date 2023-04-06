<?php

namespace App\Classes\Database;

use App\Models\PartnerIntegration;
use Exception;

class SqlUser extends Connection
{
    /**
     * Create user in specified database
     *
     * @param  string $username The name of the user
     * @param  string $password The password for the user
     * @return bool
     * @todo make static
     */
    public function createUser(PartnerIntegration $database, string $username, string $password)
    {
        return $this->connect($database)
            ->statement("CREATE USER {$username} WITH PASSWORD '{$password}';");
    }

    /**
     * Get a list of users in the specified database server
     * 
     * @todo make static
     */
    public function getUsers(PartnerIntegration $database)
    {
        return $this->connect($database)
            ->select("SELECT usename FROM pg_user;");
    }

    /**
     * Alter user password in specified database
     *
     * @param  string $username The name of the user
     * @param  string $password The password for the user
     * @return bool
     * @todo add tests
     * @todo make static
     */
    public function setPassword(PartnerIntegration $database, $username, $password)
    {
        return $this->connect($database)
            ->statement("ALTER USER {$username} WITH PASSWORD '{$password}'");
    }

    /**
     * Grant user access to schema
     *
     * @param string $username The username to grant access
     * @param string $schema   The schema to grant access to
     * @todo make static
     */
    public function grantAccessToSchema(PartnerIntegration $database, $username, $schema): bool
    {
        try {
            self::revokePublicAccessToSchema($database, $schema);
            
            self::grantUsage($database, $username, $schema);
            
            self::grantRead($database, $username, $schema);

            return TRUE;
        } catch (Exception $e) {
            logger()->error(
                "Could not create user {$username} in database {$database->database} in server {$database->server_id}.",
                [
                    'database'  => $database->id,
                    'user'      => $username,
                    'schema'    => $schema,
                    'exception' => $e,
                ]
            );

            return FALSE;
        }
    }

    /**
     * Reassign ownership and drop a user in the specified database
     *
     * @param  string $username      The user to drop
     * @param  string $default_owner The new owner of formerly owned objects
     * @return bool
     * @todo add tests
     * @todo make static
     */
    public function drop(PartnerIntegration $database, $username, $default_owner)
    {
        $connection = $this->connect($database);

        if (! self::exists($database, $username)) {
            return TRUE;
        }

        $sql = <<<SQL
            REASSIGN OWNED BY {$username} TO {$default_owner}
            SQL;

        $connection->statement($sql);

        $sql = <<<SQL
            DROP OWNED BY {$username}
            SQL;

        $connection->statement($sql);

        $sql = <<<SQL
            DROP USER IF EXISTS {$username}
            SQL;

        return $connection->statement($sql);
    }

    /**
     * Does a user already exist?
     */
    public static function exists(PartnerIntegration $database, string $user): bool
    {
        $check = self::connect($database)
            ->select("SELECT 1 FROM pg_roles WHERE rolname = ?", [$user]);

        return ! empty($check);
    }

    /**
     * Grant read only access to all objects in a schema
     */
    public static function grantReadOnlyAccessToSchema(PartnerIntegration $database, string $username, string $schema): void
    {
        self::revokePublicAccessToSchema($database, $schema);

        self::grantUsage($database, $username, $schema);

        self::grantRead($database, $username, $schema);
    }

    /**
     * Remove access to all in the public role, allowing us to safely assign permissions. Does not affect existing users or superusers.
     */
    public static function revokePublicAccessToSchema(PartnerIntegration $database, string $schema): void
    {
        self::connect($database)->statement("REVOKE ALL ON schema {$schema} FROM public");
    }

    /**
     * Revoke access to a schema 
     * When $all_tables is true, permissions for all tables in the schema will be revoked
     */
    public static function revokeAccessToSchema(PartnerIntegration $database, string $user, string $schema, bool $all_tables = TRUE): void
    {
        if ($all_tables) {
            self::connect($database)->statement("REVOKE ALL ON ALL TABLES IN schema {$schema} FROM {$user}");
        }

        self::connect($database)->statement("REVOKE ALL ON schema {$schema} FROM {$user}");
    }

    /**
     * Grant a user read access to objects within a schema
     * Provide a table name to grant access to a single table
     */
    public static function grantRead(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        $connection = self::connect($database);

        if ($table) {
            $connection->statement("GRANT SELECT ON TABLE {$table} TO {$username}");
            $connection->statement("GRANT REFERENCES ON TABLE {$table} TO {$username}");
        }

        $connection->statement("GRANT SELECT ON ALL TABLES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("GRANT REFERENCES ON ALL TABLES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT SELECT ON TABLES TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT USAGE, SELECT ON SEQUENCES TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT REFERENCES ON TABLES TO {$username}");
    }

    /**
     * Aliased for read, write, destroy permissions
     * Provide a table name to grant write permissions on a specific table
     */
    public static function grantWrite(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantInsert($database, $username, $schema, $table);
        self::grantUpdate($database, $username, $schema, $table);
    }

    /**
     * Aliased for read, write, destroy permissions
     * Provide a table name to grant destroy permissions on a specific table
     */
    public static function grantDestroy(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantDelete($database, $username, $schema, $table);
        self::grantTruncate($database, $username, $schema, $table);
    }

    /**
     * Grant read, write, destroy access to an object or all objects in a schema
     * Provide a table name to grant access to a single table
     */
    public static function grantAll(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantUsage($database, $username, $schema);
        self::grantRead($database, $username, $schema, $table);
        self::grantWrite($database, $username, $schema, $table);
        self::grantDestroy($database, $username, $schema, $table);
        self::grantCreate($database, $username, $schema);
    }

    /**
     * Grant a user permission to update records within all tables within a schema
     * Provide a table name to grant update permissions on a specific table
     */
    public static function grantUpdate(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantUsage($database, $username, $schema);

        $connection = self::connect($database);

        if ($table) {
            $connection->statement("GRANT UPDATE ON TABLE {$table} TO {$username}");

            return;
        }

        $connection->statement("GRANT UPDATE ON ALL TABLES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT UPDATE ON TABLES TO {$username}");
    }

    /**
     * Grant a user permission to delete records within all tables within a schema
     * Provide a table name to grant delete permissions on a specific table
     */
    public static function grantDelete(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantUsage($database, $username, $schema);

        $connection = self::connect($database);

        if ($table) {
            $connection->statement("GRANT DELETE ON TABLE {$table} TO {$username}");

            return;
        }

        $connection->statement("GRANT DELETE ON ALL TABLES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT DELETE ON TABLES TO {$username}");
    }

    /**
     * Grant a user permission to insert records into all tables within a schema
     * Provide a table name to grant insert permissions on a specific table
     */
    public static function grantInsert(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantUsage($database, $username, $schema);

        $connection = self::connect($database);

        if ($table) {
            $connection->statement("GRANT INSERT ON TABLE {$table} TO {$username}");

            return;
        }

        $connection->statement("GRANT INSERT ON ALL TABLES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT INSERT ON TABLES TO {$username}");
    }

    /**
     * Grant a user permission to truncate tables within a schema
     * Provide a table name to grant truncate permissions on a specific table
     */
    public static function grantTruncate(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantUsage($database, $username, $schema);

        $connection = self::connect($database);

        if ($table) {
            $connection->statement("GRANT TRUNCATE ON TABLE {$table} TO {$username}");

            return;
        }
        
        $connection->statement("GRANT TRUNCATE ON ALL TABLES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT TRUNCATE ON TABLES TO {$username}");
    }

    /**
     * Grant a user permission to create a trigger on tables within a schema
     * Provide a table name to grant trigger permissions on a specific table
     */
    public static function grantTrigger(PartnerIntegration $database, string $username, string $schema, ?string $table = NULL): void
    {
        self::grantUsage($database, $username, $schema);

        $connection = self::connect($database);

        if ($table) {
            $connection->statement("GRANT TRIGGER ON TABLE {$table} TO {$username}");

            return;
        }

        $connection->statement("GRANT TRIGGER ON ALL TABLES IN SCHEMA {$schema} TO {$username}");
        $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$schema} GRANT TRIGGER ON TABLES TO {$username}");
    }

    /**
     * Grant a user permission to use a schema. This is required before any other permissions can be granted
     */
    public static function grantUsage(PartnerIntegration $database, string $username, string $schema): void
    {
        self::connect($database)
            ->statement("GRANT USAGE ON SCHEMA {$schema} TO {$username}");
    }

    /**
     * Grant a user permission to create (views, tables, procedures) within a schema
     */
    public static function grantCreate(PartnerIntegration $database, string $username, string $schema): void
    {
        self::grantUsage($database, $username, $schema);

        self::connect($database)
            ->statement("GRANT CREATE ON SCHEMA {$schema} TO {$username}");
    }
}
