<?php

namespace App\Classes\Database;
use Generator;

class Insert extends Connection
{
    // Build out chunks to be used for upserts. This is necessary because sometimes, repeated data
    // can appear in the same insert, causing errors when trying to upsert. This function
    // will break up the insert into chunks that contain unique data.
    public static function makeSafeForUpsert(array $insert_data, ?array $upsert_keys = NULL): Generator
    {
        if (! is_array($upsert_keys) || empty($upsert_keys)) {
            yield [$insert_data];

            return;
        }

        $arrays_to_return = [
            [
                'insert' => [],
                'hashes' => [],
            ]
        ];

        /*
         * 1. Create a hash of each insert's unique key values
         * 2. Compare the hash to the hashes in the arrays to return
         * 3. If the hash is not in the array, add it to the first array
         * 4. If the hash is in the array, create a new array and add it to the arrays to return
         */
        foreach ($insert_data as $insert) {
            $hash = self::hashArrayUsingKeys($insert, $upsert_keys);

            for ($i = 0; $i < count($arrays_to_return); ++$i) {
                if (! in_array($hash, $arrays_to_return[$i]['hashes'])) {
                    $arrays_to_return[$i]['insert'][] = $insert;
                    $arrays_to_return[$i]['hashes'][] = $hash;
                    break;
                }
                
                // At the end of the array? create a new array and inject it
                if ($i == count($arrays_to_return) - 1) {
                    $arrays_to_return[] = [
                        'insert' => [$insert],
                        'hashes' => [$hash],
                    ];
                    break;
                }
            }
        }

        // Yield each array to return
        foreach ($arrays_to_return as $ret) {
            yield $ret['insert'];
        }
    }

    /**
     * Hash an array using the keys provided
     */
    public static function hashArrayUsingKeys(array $row, array $keys = []): string
    {
        return md5(implode(',', array_map(function ($key) use ($row) {
            return json_encode($row[$key] ?? '');
        }, $keys)));
    }
}
