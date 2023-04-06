<?php

namespace Tests\Feature\Classes\Database;

use Tests\TestCase;
use App\Classes\Database\Insert;

class InsertTest extends TestCase
{
    public function test_hash_array_using_keys(): void
    {
        $hash = Insert::hashArrayUsingKeys([
            'field_1' => 'value_1',
            'field_2' => 'value_2',
            'field_3' => 3,
        ], ['field_1', 'field_3']);

        $expected_value = md5(
            json_encode('value_1') .
            ',' . 
            json_encode(3)
        );

        $this->assertEquals($expected_value, $hash);
    }

    public function test_make_safe_for_upsert_with_null(): void
    {
        $insert_data = [
            [
                'field_1' => 'value_1',
                'field_2' => 'value_2',
                'field_3' => 3,
            ],
            [
                'field_1' => 'value_1',
                'field_2' => 'value_2',
                'field_3' => 3,
            ],
        ];

        $upsert_keys = NULL;

        $generator = Insert::makeSafeForUpsert($insert_data, $upsert_keys);

        $this->assertEquals([$insert_data], $generator->current());
    }

    public function test_make_safe_for_upsert_with_single_unique_key(): void
    {
        $insert_data = [
            [
                'field_1' => 'value_1',
                'field_2' => 'value_2',
                'field_3' => 'value_3',
            ],
            [
                'field_1' => 'value_1',
                'field_2' => 'value_2_different',
                'field_3' => 'value_3_different',
            ],
        ];

        $upsert_keys = ['field_1'];

        $generator = Insert::makeSafeForUpsert($insert_data, $upsert_keys);

        $this->assertEquals([$insert_data[0]], $generator->current());
        $generator->next();
        $this->assertEquals([$insert_data[1]], $generator->current());
    }

    public function test_make_safe_for_upsert_with_compound_unique_key(): void
    {
        $insert_data = [
            [
                'field_1' => 'value_1',
                'field_2' => 'value_2',
                'field_3' => 'value_3',
            ],
            [ // this array item should be added to a second yielded array because its values in field_1 and field_3 match the 0 index array item
                'field_1' => 'value_1',
                'field_2' => 'value_2_different',
                'field_3' => 'value_3',
            ],
            [
                'field_1' => 'value_1',
                'field_2' => 'value_2',
                'field_3' => 'value_3_different',
            ]
        ];

        $upsert_keys = ['field_1', 'field_3'];

        $generator = Insert::makeSafeForUpsert($insert_data, $upsert_keys);

        $this->assertEquals([$insert_data[0], $insert_data[2]], $generator->current());
        $generator->next();
        $this->assertEquals([$insert_data[1]], $generator->current());
    }

    public function test_make_safe_for_upsert_with_50_duplicates(): void
    {
        $insert_data = [];
        for ($i = 0; $i < 50; ++$i) {
            $insert_data[] = [
                'field_1' => 'value_1',
                'field_2' => 'value_' . $i,
                'field_3' => 'value_3',
            ];
            $insert_data[] = [
                'field_a' => 'value_a',
                'field_b' => 'value_' . ($i + 1),
                'field_c' => 'value_c',
            ];
        }

        $upsert_keys = ['field_1', 'field_3'];
        $generated_inserts = [];

        foreach (Insert::makeSafeForUpsert($insert_data, $upsert_keys) as $insert) {
            $generated_inserts[] = $insert;
        }

        $this->assertCount(50, $generated_inserts);

        $this->assertEquals(
            [$insert_data[98], $insert_data[99]],
            $generated_inserts[49]
        );
    }
}
