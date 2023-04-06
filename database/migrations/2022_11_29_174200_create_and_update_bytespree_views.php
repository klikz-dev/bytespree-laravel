<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $sql = <<<SQL
            CREATE SCHEMA IF NOT EXISTS "__bytespree"
            SQL;

        DB::statement($sql);

        DB::statement('DROP VIEW IF EXISTS "__bytespree"."v_dw_jenkins_builds__latest_syncs_by_database"');

        $sql = <<<SQL
            CREATE VIEW __bytespree.v_dw_jenkins_builds__latest_syncs_by_database AS
            WITH builds AS (
                SELECT 
                    DISTINCT ON (parameters->>5, (parameters->>4)::bigint) 
                    parameters->>5 as job_name, 
                    (parameters->>4)::bigint AS database_id,
                    result, 
                    id
                FROM dw_jenkins_builds b
                WHERE deleted_at IS NULL
                    AND parameters->>2 = 'Run'
                    AND parameters->>3 = 'sync'
                    AND parameters->>5 IS NOT NULL
            )
            SELECT 
                b.* 
            FROM builds b
            JOIN di_partner_integration_tables t 
            ON 
                b.database_id = t.partner_integration_id 
                AND b.job_name = t.name 
                AND t.deleted_at IS NULL 
            ORDER BY 
                job_name,
                database_id,
                b.id DESC
            SQL;

        DB::statement($sql);

        DB::statement('DROP VIEW IF EXISTS "__bytespree"."v_dw_jenkins_builds__latest_no_table_syncs_by_database"');

        $sql = <<<SQL
            CREATE VIEW "__bytespree"."v_dw_jenkins_builds__latest_no_table_syncs_by_database" AS
            SELECT     
                DISTINCT ON ((parameters->>4)::bigint) 
                (parameters->>4)::bigint as database_id, 
                result, 
                id
            FROM dw_jenkins_builds
            WHERE deleted_at IS NULL 
                  AND parameters->>2 = 'Run'
                  AND parameters->>3 = 'sync'
                  AND parameters->>5 IS NULL
            ORDER BY (parameters->>4)::bigint, id DESC
            SQL;

        DB::statement($sql);
        
        DB::statement('DROP VIEW IF EXISTS "__bytespree"."v_dw_jenkins_builds__latest_tests_by_database"');
        
        $sql = <<<SQL
            CREATE VIEW "__bytespree"."v_dw_jenkins_builds__latest_tests_by_database" AS 
            SELECT 
                DISTINCT ON ((parameters->>4)::bigint) 
                (parameters->>4)::bigint as database_id, 
                result, 
                id
            FROM dw_jenkins_builds
            WHERE deleted_at IS NULL 
                AND parameters->>2 = 'Run'
                AND parameters->>3 = 'test'
            ORDER BY (parameters->>4)::bigint, id DESC
            SQL;

        DB::statement($sql);

        DB::statement('DROP VIEW IF EXISTS __bytespree.v_dw_jenkins_builds__latest_builds_by_database');
        
        $sql = <<<SQL
            CREATE VIEW __bytespree.v_dw_jenkins_builds__latest_builds_by_database AS
            WITH builds AS (
                SELECT 
                    DISTINCT ON (parameters->>5, (parameters->>4)::bigint) 
                    parameters->>5 as job_name, 
                    (parameters->>4)::bigint AS database_id,
                    result, 
                    id
                FROM dw_jenkins_builds b
                WHERE deleted_at IS NULL 
                    AND parameters->>2 = 'Run'
                    AND parameters->>3 = 'build'
                    AND parameters->>5 IS NOT NULL
            )
            SELECT 
                b.* 
            FROM builds b
            JOIN di_partner_integration_tables t 
            ON 
                b.database_id = t.partner_integration_id 
                AND b.job_name = t.name 
                AND t.deleted_at IS NULL
            ORDER BY 
                job_name,
                database_id,
                b.id DESC
            SQL;

        DB::statement($sql);

        DB::statement('DROP VIEW IF EXISTS __bytespree.v_dw_jenkins_builds__latest_no_table_builds_by_database');

        $sql = <<<SQL
            CREATE VIEW "__bytespree"."v_dw_jenkins_builds__latest_no_table_builds_by_database" AS
            SELECT 
                DISTINCT ON ((parameters->>4)::bigint) 
                (parameters->>4)::bigint as database_id, 
                result, 
                id
            FROM dw_jenkins_builds
            WHERE deleted_at IS NULL
                  AND parameters->>2 = 'Run'
                  AND parameters->>3 = 'build'
                  AND parameters->>5 IS NULL
            ORDER BY (parameters->>4)::bigint, id DESC
            SQL;

        DB::statement($sql);

        $sql = <<<SQL
            CREATE INDEX IF NOT EXISTS dw_jenkins_builds_job_name_database_id_idx
            ON dw_jenkins_builds (
                job_name,
                ((parameters->>4)::bigint)
            )
            WHERE parameters->>2 = 'Run'
            SQL;

        DB::statement($sql);

        DB::statement('DROP VIEW IF EXISTS __bytespree.v_dw_jenkins_builds__latest_results_by_database');
        
        DB::statement('DROP VIEW IF EXISTS __bytespree.v_dw_jenkins_builds__latest_results_by_job');
        
        $sql = <<<SQL
            CREATE VIEW __bytespree.v_dw_jenkins_builds__latest_results_by_job AS
            WITH
            latest_runs as (
                SELECT job_name,  (parameters->>4)::bigint AS database_id, max(id) AS id
                FROM dw_jenkins_builds
                WHERE parameters->>2 = 'Run'
                GROUP BY job_name, (parameters->>4)::bigint
            ),
            latest_syncs AS (
                SELECT
                    b.job_name, 
                    (parameters->>4)::bigint AS database_id,
                    parameters->>3 AS job_type,
                    "result",
                    case "result"
                        when 'SUCCESS' then 10
                        when 'ABORTED' then 20
                        when 'FAILURE' then 30
                        else 100
                    end result_code,
                    b.id
                FROM dw_jenkins_builds as b
                JOIN latest_runs AS lr ON lr.job_name = b.job_name AND lr.database_id = (parameters->>4)::bigint AND lr.id = b.id
                JOIN di_partner_integration_tables t 
                ON 
                    (parameters->>4)::bigint = t.partner_integration_id 
                    AND b.job_name = t.name 
                    AND t.deleted_at IS NULL
                WHERE b.deleted_at IS NULL
                    AND parameters->>2 = 'Run'
                    AND parameters->>3 = 'sync'
            ),
            latest_builds AS (
                SELECT 
                    b.job_name, 
                    (parameters->>4)::bigint AS database_id,
                    parameters->>3 AS job_type,
                    "result",
                    case "result"
                        when 'SUCCESS' then 10
                        when 'ABORTED' then 20
                        when 'FAILURE' then 30
                        else 100
                    end result_code,
                    b.id
                FROM dw_jenkins_builds as b
                JOIN latest_runs AS lr ON lr.job_name = b.job_name AND lr.database_id = (parameters->>4)::bigint AND lr.id = b.id
                JOIN di_partner_integration_tables t 
                ON 
                    (parameters->>4)::bigint = t.partner_integration_id 
                    AND b.job_name = t.name 
                    AND t.deleted_at IS NULL
                WHERE b.deleted_at IS NULL
                    AND parameters->>2 = 'Run'
                    AND parameters->>3 = 'build'
            ),
            latest_tests as (
                SELECT 
                    b.job_name,
                    (parameters->>4)::bigint as database_id, 
                    parameters->>3 AS job_type,
                    "result",
                    case "result"
                        when 'SUCCESS' then 10
                        when 'ABORTED' then 40
                        when 'FAILURE' then 50
                        else 100
                    end result_code,
                    b.id
                FROM dw_jenkins_builds as b
                JOIN latest_runs AS lr ON lr.job_name = b.job_name AND lr.database_id = (parameters->>4)::bigint AND lr.id = b.id
                JOIN di_partner_integration_tables t 
                ON 
                    (parameters->>4)::bigint = t.partner_integration_id 
                    AND b.job_name = t.name 
                    AND t.deleted_at IS NULL
                WHERE b.deleted_at IS NULL
                    AND parameters->>2 = 'Run'
                    AND parameters->>3 = 'test'
            ),
            latest_syncs_no_tables as (
                SELECT
                    b.job_name,
                    (parameters->>4)::bigint as database_id,
                    parameters->>3 AS job_type,
                    "result",
                    case "result"
                        when 'SUCCESS' then 10
                        when 'ABORTED' then 20
                        when 'FAILURE' then 30
                        else 100
                    end result_code,
                    b.id
                FROM dw_jenkins_builds AS b
                JOIN latest_runs AS lr ON lr.job_name = 'sync' AND lr.database_id = (parameters->>4)::bigint AND lr.id = b.id
                JOIN di_partner_integrations pi ON (parameters->>4)::bigint = pi.id AND pi.deleted_at IS null
                JOIN di_integrations i ON i.id = pi.integration_id AND i.use_tables = FALSE AND i.deleted_at IS null
                WHERE parameters->>2 = 'Run'
                    AND parameters->>3 = 'sync'
                    AND parameters->>5 IS NULL
            ),
            latest_builds_no_tables as (
                SELECT 
                    b.job_name,
                    (parameters->>4)::bigint as database_id,
                    parameters->>3 AS job_type, 
                    "result",
                    case "result"
                        when 'SUCCESS' then 10
                        when 'ABORTED' then 20
                        when 'FAILURE' then 30
                        else 100
                    end result_code,
                    b.id
                FROM dw_jenkins_builds AS b
                JOIN latest_runs AS lr ON lr.job_name = 'build' AND lr.database_id = (parameters->>4)::bigint AND lr.id = b.id
                JOIN di_partner_integrations pi ON (parameters->>4)::bigint = pi.id AND pi.deleted_at IS null
                JOIN di_integrations i ON i.id = pi.integration_id AND i.use_tables = FALSE AND i.deleted_at IS null
                WHERE parameters->>2 = 'Run'
                    AND parameters->>3 = 'build'
                    AND parameters->>5 IS NULL
            ), 
            builds as (
                SELECT
                    id,
                    database_id,
                    job_name,
                    job_type,
                    "result",
                    result_code
                FROM latest_tests 
                UNION
                SELECT 
                    id,
                    database_id,
                    job_name,
                    job_type,
                    "result",
                    result_code
                FROM latest_syncs 
                UNION
                SELECT 
                    id,
                    database_id,
                    job_name,
                    job_type,
                    "result",
                    result_code
                FROM latest_builds
                UNION
                SELECT 
                    id,
                    database_id,
                    job_name,
                    job_type,
                    "result",
                    result_code
                FROM latest_syncs_no_tables
                UNION
                SELECT 
                    id,
                    database_id,
                    job_name,
                    job_type,
                    "result",
                    result_code
                FROM latest_builds_no_tables
            )
            SELECT
                id,
                database_id, 
                job_name, 
                job_type,
                "result",
                result_code,
                CASE result_code
                    WHEN 20 THEN 'red'
                    WHEN 30 THEN 'red'
                    WHEN 40 THEN 'yellow'
                    WHEN 50 THEN 'yellow'
                    ELSE 'blue'
                END AS status_color,
                CASE result_code
                    WHEN 100 THEN TRUE
                    ELSE FALSE
                END AS is_running
            from
                builds
            ORDER BY 
                database_id,
                job_name
            SQL;

        DB::statement($sql);    

        $sql = <<<SQL
            CREATE VIEW __bytespree.v_dw_jenkins_builds__latest_results_by_database AS
            WITH 
            failed_jobs_by_database AS (
                SELECT database_id, job_name 
                FROM __bytespree.v_dw_jenkins_builds__latest_results_by_job 
                WHERE result_code BETWEEN 20 AND 99
            )
            SELECT 
                b.database_id, 
                max(result_code) AS result_code, 
                CASE max(result_code)
                    WHEN 20 THEN 'red'
                    WHEN 30 THEN 'red'
                    WHEN 40 THEN 'yellow'
                    WHEN 50 THEN 'yellow'
                    ELSE 'blue'
                END AS status_color,
                json_agg(DISTINCT fj.job_name) AS failed_jobs,
                CASE max(result_code)
                    WHEN 100 THEN TRUE 
                    ELSE FALSE 
                END AS is_running
            FROM __bytespree.v_dw_jenkins_builds__latest_results_by_job AS b
            LEFT JOIN failed_jobs_by_database fj ON fj.database_id = b.database_id
            GROUP BY b.database_id
            SQL;

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
