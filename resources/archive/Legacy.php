<?php

    /**
     * Terminate processes that have a view locked
     *
     * @param  CI_DB_query_builder $dbconn        Existing database connection
     * @param  string              $object_name   Name of object with locks
     * @param  string              $object_schema Schema containing object
     * @param  array               $object_kinds  (Optional) Kinds of objects to look for
     *                                            r = table,
     *                                            v = view,
     *                                            m = materialized view,
     *                                            f = foreign table
     * @return bool                TRUE if all proceses locking object were terminated or if no locks were found, otherwise FALSE
     */
    protected function terminateProcessesLockingObject($dbconn, $object_name, $object_schema, $object_kinds = ['r', 'v', 'm', 'f'])
    {
        if (empty($object_kinds) && ! is_array($object_kinds)) {
            throw new InvalidArgumentException("Must provide at least one kind of object in object_kinds array");
        }
        $available = TRUE;
        array_walk($object_kinds, function (&$value, $key) {
            $value = "'$value'";
        });
        $kinds = implode(',', $object_kinds);
        $sql = <<<SQL
            select pg_terminate_backend(pid) terminated, pid, state, usename, query, query_start 
            from pg_stat_activity 
            where pid in (
                select pid
                    from pg_locks l 
                    join pg_class t on l.relation = t.oid 
                    join pg_namespace n 
                    on 
                        n.oid = t.relnamespace
                        and t.relkind in ($kinds)
                    where 
                        t.relname = ?
                        and n.nspname = ?
            )
            SQL;

        $query = $dbconn->query($sql, [$object_name, $object_schema]);
        if ($query) {
            $results = $query->result_array();
            foreach ($results as $result) {
                if ($result['terminated'] === FALSE) {
                    $available = FALSE;
                    $this->logging->error(
                        "Could not terminate a process locking object $object_name",
                        [
                            'object_schema' => $object_schema,
                            'object_name'   => $object_name,
                            'pid'           => $result['pid'],
                            'state'         => $result['state'],
                            'usename'       => $result['usename'],
                            'query'         => $result['query'],
                            'query_start'   => $result['query_start']
                        ]
                    );
                }
            }
        }

        return $available;
    }