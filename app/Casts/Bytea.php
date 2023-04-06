<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Bytea implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  mixed                               $value
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  mixed                               $value
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        // Create and return a stream from a string value
        $binary_stream = fopen('php://memory', 'r+');
        fwrite($binary_stream, $value);
        rewind($binary_stream);

        return $binary_stream;
    }
}
