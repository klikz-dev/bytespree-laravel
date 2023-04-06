<?php

namespace App\Http\Controllers\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\InternalApi\V1\DataLake\CompareController as InternalCompareController;
use App\Models\PartnerIntegration;
use App\Attributes\Can;

class CompareController extends Controller
{
    #[Can(permission: 'datalake_access', product: 'datalake')]
    public function index()
    {
        return view('compare');
    }

    /**
     * Download a CSV from a comparison
     */
    public function csv(Request $request)
    {
        $data = base64_decode($request->body);
        $data = json_decode($data);

        $request->merge((array) $data);

        $database_left = PartnerIntegration::find($request->database_left);
        $database_right = PartnerIntegration::find($request->database_right);
        
        $all_columns = app(InternalCompareController::class)->compare($request, TRUE);

        setcookie("downloadStarted", 1, time() + 60, '/', "", FALSE, FALSE);
        ob_clean();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', FALSE);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=compare.csv');

        $fp = fopen('php://output', 'w');
        $headers = [];
        $not_found_text = '[ Column not found ]';

        if ($request->show_all_col_definitions) {
            $headers = [
                $database_left->database . "." . $request->table_name_left . ".data_type",
                $database_left->database . "." . $request->table_name_left . ".char_max_len",
                $database_left->database . "." . $request->table_name_left . ".precision",
                $database_left->database . "." . $request->table_name_left,
                $database_right->database . "." . $request->table_name_right,
                $database_right->database . "." . $request->table_name_right . ".precision",
                $database_right->database . "." . $request->table_name_right . ".char_max_len",
                $database_right->database . "." . $request->table_name_right . ".data_type",
            ];

            fputcsv($fp, $headers);

            foreach ($all_columns as $name => $values) {
                $output = [];
                if (isset($values['left']) && $values['left']) {
                    $output[] = $values['left_data_type'];
                    $output[] = $values['left_character_maximum_length'];
                    $output[] = $values['left_numeric_precision'];
                    $output[] = $values['left_column_name'];
                } else {
                    $output[] = "";
                    $output[] = "";
                    $output[] = "";
                    $output[] = $not_found_text;
                }
                if (isset($values['right']) && $values['right']) {
                    $output[] = $values['right_column_name'];
                    $output[] = $values['right_numeric_precision'];
                    $output[] = $values['right_character_maximum_length'];
                    $output[] = $values['right_data_type'];
                } else {
                    $output[] = $not_found_text;
                    $output[] = "";
                    $output[] = "";
                    $output[] = "";
                }
                fputcsv($fp, $output);
            }
        } else {
            $headers[] = $database_left->database . "." . $request->table_name_left;
            $headers[] = $database_right->database . "." . $request->table_name_right;
            fputcsv($fp, $headers);
            foreach ($all_columns as $name => $values) {
                $output = [];
                if (isset($values['left']) && $values['left']) {
                    $output[] = $values['left_column_name'];
                } else {
                    $output[] = $not_found_text;
                }
                if (isset($values['right']) && $values['right']) {
                    $output[] = $values['right_column_name'];
                } else {
                    $output[] = $not_found_text;
                }
                fputcsv($fp, $output);
            }
        }
    }
}
