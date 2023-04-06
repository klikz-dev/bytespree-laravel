<?php

namespace Tests\Feature\Classes\Database;

use App\Classes\Database\{Connection, SqlUser};
use App\Models\{PartnerIntegration, Server};
use Illuminate\Database\{PostgresConnection, QueryException};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Note: This test class requires a local postgres server to be running.
 *        It creates a database named 'unit_testing_partnerintegration_database' and a user named 'phpunit_test_user'
 *        If the database or user already exist, they will be dropped and recreated.
 * 
 * @group database
 */
class SqlUserTest extends TestCase
{
    use DatabaseTransactions;

    private string $db_name = 'unit_testing_partnerintegration_database';
    private string $password = 'phpunit_test_password';
    private string $schema = 'public';
    private string $username = 'phpunit_test_user';

    private PartnerIntegration $database;
    private Server $server;

    public function setUp(): void
    {
        parent::setUp();

        // Create memory-only instances of Server && PartnerIntegration objects to use for our tests
        $this->server = new Server([
            'name'     => 'testing',
            'hostname' => config('database.connections.pgsql.host'),
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
            'port'     => config('database.connections.pgsql.port'),
            'driver'   => 'postgres',
            'database' => 'postgres',
        ]);

        $this->database = new PartnerIntegration([
            'database' => $this->db_name,
        ]);

        $this->database->setRelation('server', $this->server);

        // Ensure our database exists
        $results = DB::select("SELECT 1 WHERE EXISTS (SELECT FROM pg_database WHERE datname = '{$this->db_name}')");

        if (! $results || count($results) === 0) {
            DB::statement("CREATE DATABASE {$this->db_name}");
        }
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_get_users()
    {
        $this->assertGreaterThan(
            0,
            count(app(SqlUser::class)->getUsers($this->database))
        );
    }

    public function test_create_user()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            $this->assertTrue(
                app(SqlUser::class)->createUser($this->database, $this->username, $this->password)
            );

            $users = collect(app(SqlUser::class)->getUsers($this->database))->pluck('usename');

            $this->assertTrue($users->contains($this->username));
        });
    }

    public function test_grant_access_to_schema()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            $this->assertFalse(
                app(SqlUser::class)->grantAccessToSchema($this->database, 'user_that_does_not_exist', $this->schema)
            );
        });
        
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            $this->assertFalse(
                app(SqlUser::class)->grantAccessToSchema($this->database, $this->username, 'schema_that_does_not_exist')
            );
        });

        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            $this->assertTrue(
                app(SqlUser::class)->grantAccessToSchema($this->database, $this->username, $this->schema)
            );
        });
    }

    public function test_exists()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            $this->assertFalse(
                app(SqlUser::class)->exists($this->database, 'user_that_does_not_exist')
            );
        });

        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            $this->assertTrue(
                app(SqlUser::class)->exists($this->database, $this->username)
            );
        });
    }

    public function test_grant_read_only_access_to_schema()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            $connection->statement('create table temp_table (id int default null)');

            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);
            
            app(SqlUser::class)->grantReadOnlyAccessToSchema($this->database, $this->username, $this->schema);

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");

            $this->assertEquals(
                [],
                $connection->select('select * from temp_table')
            );
        });
    }

    public function test_grant_create()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            SqlUser::revokePublicAccessToSchema($this->database, $this->schema);

            SqlUser::grantCreate($this->database, $this->username, $this->schema);

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");

            $this->assertTrue(
                $connection->statement('create table public.temp_table (id int default null)')
            );
        });
    }

    public function test_grant_destroy()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            SqlUser::revokePublicAccessToSchema($this->database, $this->schema);
            
            SqlUser::grantCreate($this->database, $this->username, $this->schema);
            app(SqlUser::class)->grantDestroy($this->database, $this->username, $this->schema);
            
            $connection->statement('create table public.temp_table (id int default null)');

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");

            $this->assertTrue(
                $connection->statement('delete from public.temp_table')
            );
            
            $this->assertTrue(
                $connection->statement('truncate table public.temp_table')
            );
        });
    }

    public function test_grant_destroy_with_specific_table()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            SqlUser::revokePublicAccessToSchema($this->database, $this->schema);
            
            SqlUser::grantCreate($this->database, $this->username, $this->schema);
            
            $connection->statement('create table public.temp_table (id int default null)');
            $connection->statement('create table public.temp_table_2 (id int default null)');

            SqlUser::grantDestroy($this->database, $this->username, $this->schema, 'temp_table');

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");

            $this->assertTrue(
                $connection->statement('delete from public.temp_table')
            );
            
            $this->assertTrue(
                $connection->statement('truncate table public.temp_table')
            );

            $this->expectException(QueryException::class);

            $connection->statement('delete from public.temp_table_2');

            $this->expectException(QueryException::class);

            $connection->statement('truncate table public.temp_table_2');
        });
    }

    public function test_grant_insert()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            SqlUser::revokePublicAccessToSchema($this->database, $this->schema);
            
            app(SqlUser::class)->grantInsert($this->database, $this->username, $this->schema);
            
            $connection->statement('create table public.temp_table (id int default null)');

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");
            
            $this->assertTrue(
                $connection->statement('insert into public.temp_table (id) values (1)')
            );
        });
    }

    public function test_grant_insert_with_specific_table()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            SqlUser::revokePublicAccessToSchema($this->database, $this->schema);
            
            $connection->statement('create table public.temp_table (id int default null)');
            $connection->statement('create table public.temp_table2 (id int default null)');

            app(SqlUser::class)->grantInsert($this->database, $this->username, $this->schema, 'temp_table');

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");
            
            $this->assertTrue(
                $connection->statement('insert into public.temp_table (id) values (1)')
            );

            $this->expectException(QueryException::class);

            $connection->statement('insert into public.temp_table2 (id) values (1)');
        });
    }

    public function test_grant_update()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            SqlUser::revokePublicAccessToSchema($this->database, $this->schema);

            app(SqlUser::class)->grantUpdate($this->database, $this->username, $this->schema);
            
            $connection->statement('create table public.temp_table (id int default null)');

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");

            $this->assertTrue(
                $connection->statement('update public.temp_table set id = 1')
            );
        });
    }

    public function test_grant_update_with_specific_table()
    {
        Connection::transactionWithRollback($this->database, function (PostgresConnection $connection) {
            app(SqlUser::class)->createUser($this->database, $this->username, $this->password);

            SqlUser::revokePublicAccessToSchema($this->database, $this->schema);
            
            $connection->statement('create table public.temp_table (id int default null)');

            app(SqlUser::class)->grantUpdate($this->database, $this->username, $this->schema, 'temp_table');

            $connection->statement('create table public.temp_table2 (id int default null)');

            // Switch to our newly created user/role
            $connection->statement("set role {$this->username}");

            // Should pass, as we have permission temp_table
            $this->assertTrue(
                $connection->statement('update public.temp_table set id = 1')
            );

            $this->expectException(QueryException::class);

            // Should throw an exception, as we don't have permission on temp_table2
            $connection->statement('update public.temp_table2 set id = 1');
        });
    }
}
