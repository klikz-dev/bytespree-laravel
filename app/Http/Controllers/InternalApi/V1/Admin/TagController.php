<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Manager\Tag;

class TagController extends Controller
{
    public function list()
    {
        return response()->success(Tag::get());
    }

    public function create(Request $request)
    {
        $request->validateWithErrors([
            'color' => 'required',
            'name'  => 'required'
        ]);

        if (Tag::where('name', $request->name)->exists()) {
            return response()->error('A tag with this name already exists.');
        }
        
        Tag::create($request->all());

        return response()->success();
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'color' => 'required',
            'name'  => 'required'
        ]);

        $site = Tag::find($id)->update($request->all());

        return response()->success();
    }

    public function destroy(int $id)
    {
        Tag::find($id)->delete();

        return response()->empty();
    }
}
