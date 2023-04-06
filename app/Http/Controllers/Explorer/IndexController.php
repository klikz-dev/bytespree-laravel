<?php

namespace App\Http\Controllers\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Attributes\Can;
use Auth;

class IndexController extends Controller
{
    #[Can(permission: 'studio_access', product: 'studio')]
    public function index()
    {
        $this->setCrumbs(
            'studio',
            [
                [
                    "title"    => "Studio",
                    "location" => "/studio"
                ]
            ]
        );

        $flash_error = session()->get('flash_error');

        return view('studio', compact('flash_error'));
    }
}
