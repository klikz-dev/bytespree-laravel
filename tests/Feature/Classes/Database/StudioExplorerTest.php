<?php

namespace Tests\Feature\Classes\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Classes\Database\StudioExplorer;

class StudioExplorerTest extends TestCase
{
    public function test_apply_find_replace()
    {
        $sql_fragment = app(StudioExplorer::class)->applyFindReplace('my_column', [
                'field_1' => ['value' => 'find_me'],
                'field_2' => ['value' => 'replace_with']
            ], 'varchar');

        $this->assertEquals(
            "REPLACE(CAST(my_column as text), 'find_me', 'replace_with')",
            $sql_fragment
        );
    }

    public function test_apply_if_then()
    {
        $sql_fragment = app(StudioExplorer::class)->applyIfThen('my_column', [
            [
                'field_1' => ['value' => 'first_value'],
                'field_2' => ['value' => 'first_determined_value'],
                'field_3' => ['value' => 'first_default_value'],
            ],
            [
                'field_1' => ['value' => 'second_value'],
                'field_2' => ['value' => 'second_determined_value'],
                'field_3' => ['value' => 'second_default_value'],
            ],
        ], 'varchar');

        $this->assertEquals(
            "CASE WHEN my_column first_value 'first_determined_value' THEN 'first_default_value' WHEN my_column second_value 'second_determined_value' THEN 'second_default_value' ELSE my_column END",
            $sql_fragment
        );
    }
}
