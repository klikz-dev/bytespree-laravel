<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Attributes\Can;

class PermissionMiddleware
{
    /**
     * Check a request for the presence of a permission attribute on the Controller::method.
     */
    public function handle(Request $request, Closure $next)
    {
        $method = Route::getCurrentRoute()->getActionMethod();
        $abort = FALSE;

        $ref = new ReflectionClass(Route::getCurrentRoute()->controller);

        $permission_attributes = $ref->getMethod($method)->getAttributes(Can::class);

        foreach ($permission_attributes as $attribute) {
            $args = $attribute->getArguments();

            if (array_key_exists('permission', $args)) {
                $permission = $args['permission'];
            } else {
                $permission = NULL;
            }
            
            if (array_key_exists('product', $args)) {
                $product = $args['product'];
            } else {
                $product = 'studio'; // Default to studio
            }

            if (array_key_exists('id', $args)) {
                $id = $args['id'];
            } else {
                $id = NULL;
            }

            if (is_string($id) && ! empty($id)) {
                if (! is_numeric($id)) {
                    $id = request()->route($id); // Try to pluck out our child product id
                } else {
                    $id = (int) $id; // If the id is numeric, use it instead of trying to get a route param
                }

                if (! Auth::user()->is_admin) {
                    if ($product == 'datalake') {
                        $product_child = Auth::user()->databases()->filter(function ($project) use ($id) {
                            return $project->product_child_id == $id;
                        });
                    } else if ($product == 'studio') {
                        $product_child = Auth::user()->projects()->filter(function ($project) use ($id) {
                            return $project->product_child_id == $id;
                        });
                    }

                    if ($product_child->count() == 0) {
                        $abort = TRUE;
                        break;
                    }
                }
            }

            if (! Auth::user()->hasPermissionTo($permission, $id, $product)) {
                // If the user doesn't have the permission to anything, they can still see data lake home
                if (! is_null($id) || $product != 'datalake') {
                    $abort = TRUE;
                }
            }
        }

        if ($abort) {
            abort(403);
        }

        return $next($request);
    }
}
