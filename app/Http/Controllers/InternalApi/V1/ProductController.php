<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function list()
    {
        return response()->success(
            Product::orderBy('name', 'desc')->get()->map(function ($product) {
                $display_name = str_replace('datalake', 'data lake', $product->name);
                $product->display_name = ucwords($display_name);

                return $product;
            })
        );
    }
}
