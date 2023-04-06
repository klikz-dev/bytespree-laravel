<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Classes\Database\Table;

class CompareController extends Controller
{
    public function compare(Request $request, bool $internal_request = FALSE)
    {
        $request->validateWithErrors([
            'database_left'      => 'required',
            'table_name_left'    => 'required',
            'table_schema_left'  => 'required',
            'database_right'     => 'required',
            'table_name_right'   => 'required',
            'table_schema_right' => 'required',
        ]);

        $left_database = PartnerIntegration::find($request->database_left);
        $right_database = PartnerIntegration::find($request->database_right);

        $column_data_left = Table::columns($left_database, $request->table_schema_left, $request->table_name_left);
        $column_data_right = Table::columns($right_database, $request->table_schema_right, $request->table_name_right);

        // Should we lower case column names to make it case insensitive?
        if (filter_var($request->ignore_case_differences, FILTER_VALIDATE_BOOLEAN)) {
            $column_data_left = array_map(function ($column) {
                $column->column_name = strtolower($column->column_name);

                return $column;
            }, $column_data_left);

            $column_data_right = array_map(function ($column) {
                $column->column_name = strtolower($column->column_name);

                return $column;
            }, $column_data_right);
        }        

        // Generate a hash to compare the two arrays
        $column_data_right = array_map(function ($column) {
            $column->hash = md5(
                serialize([
                    $column->column_name,
                    $column->data_type,
                    $column->character_maximum_length,
                    $column->numeric_precision,
                ])
            );

            return $column;
        }, $column_data_right);

        $column_data_left = array_map(function ($column) {
            $column->hash = md5(
                serialize([
                    $column->column_name,
                    $column->data_type,
                    $column->character_maximum_length,
                    $column->numeric_precision,
                ])
            );

            return $column;
        }, $column_data_left);

        if (filter_var($request->ignore_position_differences, FILTER_VALIDATE_BOOLEAN)) {
            $all_columns = $this->compareWithoutPositions($column_data_left, $column_data_right);
        } else {
            $all_columns = $this->compareWithPositions($column_data_left, $column_data_right);
        }

        if ($internal_request) {
            return $all_columns;
        }

        return response()->success([
            'all_columns' => $all_columns,
            'module'      => 'compare'
        ]);
    }

    public function compareWithPositions(array $column_data_left, array $column_data_right)
    {
        $column_data_left = array_values($column_data_left);
        $column_data_right = array_values($column_data_right);
        $all_columns = [];

        $max_count = count($column_data_left);
        if ($max_count < count($column_data_right)) {
            $max_count = count($column_data_right);
        }

        for ($i = 0; $i < $max_count; ++$i) {
            if (isset($column_data_left[$i])) {
                $all_columns[$i]['left'] = TRUE;
                $all_columns[$i]['left_column_name'] = $column_data_left[$i]->column_name;
                $all_columns[$i]['left_data_type'] = $column_data_left[$i]->data_type;
                $all_columns[$i]['left_character_maximum_length'] = $column_data_left[$i]->character_maximum_length;
                $all_columns[$i]['left_numeric_precision'] = $column_data_left[$i]->numeric_precision;
                $all_columns[$i]['left_hash'] = $column_data_left[$i]->hash;
            }

            if (isset($column_data_right[$i])) {
                $all_columns[$i]['right'] = TRUE;
                $all_columns[$i]['right_column_name'] = $column_data_right[$i]->column_name;
                $all_columns[$i]['right_data_type'] = $column_data_right[$i]->data_type;
                $all_columns[$i]['right_character_maximum_length'] = $column_data_right[$i]->character_maximum_length;
                $all_columns[$i]['right_numeric_precision'] = $column_data_right[$i]->numeric_precision;
                $all_columns[$i]['right_hash'] = $column_data_right[$i]->hash;
            }

            if (isset($all_columns[$i]['left_hash']) && isset($all_columns[$i]['right_hash']) && $all_columns[$i]['left_hash'] == $all_columns[$i]['right_hash']) {
                $all_columns[$i]['class'] = 'compare-equal';
            } else {
                $all_columns[$i]['class'] = 'compare-different';
            }
        }

        return $all_columns;
    }

    public function compareWithoutPositions(array $column_data_left = [], array $column_data_right = [])
    {
        $merged_columns = array_unique(array_merge(
            array_column((array) $column_data_left, 'column_name'),
            array_column((array) $column_data_right, 'column_name')
        ));

        $all_columns = [];
        
        $column_data_left = collect($column_data_left)->keyBy('column_name');
        $column_data_right = collect($column_data_right)->keyBy('column_name');

        foreach ($merged_columns as $column) {
            if ($column_data_left->has($column)) {
                $all_columns[$column]['left'] = TRUE;
                
                $col = $column_data_left->get($column);
                // Setting the column data for the base 64 of the column name
                $all_columns[$column]['left_column_name'] = $col->column_name;
                $all_columns[$column]['left_data_type'] = $col->data_type;
                $all_columns[$column]['left_character_maximum_length'] = $col->character_maximum_length;
                $all_columns[$column]['left_numeric_precision'] = $col->numeric_precision;
                $all_columns[$column]['left_column_hash'] = $col->hash;
            }

            if ($column_data_right->has($column)) {
                $all_columns[$column]['right'] = TRUE;

                $col = $column_data_right->get($column);

                // Setting the column data for the base 64 of the column name
                $all_columns[$column]['right_column_name'] = $col->column_name;
                $all_columns[$column]['right_data_type'] = $col->data_type;
                $all_columns[$column]['right_character_maximum_length'] = $col->character_maximum_length;
                $all_columns[$column]['right_numeric_precision'] = $col->numeric_precision;
                $all_columns[$column]['right_column_hash'] = $col->hash;
            }

            $all_columns[$column]['class'] = 'compare-different';
            if ((isset($all_columns[$column]['right']) && $all_columns[$column]['right'] === TRUE)
                && (isset($all_columns[$column]['left']) && $all_columns[$column]['left'] === TRUE)) {
                if ($all_columns[$column]['left_column_hash'] == $all_columns[$column]['right_column_hash']) {
                    $all_columns[$column]['class'] = 'compare-equal';
                }
            }
        }

        return array_values($all_columns);
    }

    public function tables(Request $request, PartnerIntegration $database)
    {
        $schemas = $database->projects->pluck('name')->toArray();
        
        $tables = app(Table::class)->list($database, $schemas);

        return response()->success($tables);
    }
}
