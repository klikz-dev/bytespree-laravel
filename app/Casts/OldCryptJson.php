<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use App\Classes\Cryptor;

/**
 * A cast class for models to utilize the older style of encryption.
 * 
 * Add to your model's casts for each field you want encrypted, and it handles it for you automatically.
 * 
 * protected $casts = [
 *   'my_field' => OldCrypt::class,
 *    ...,
 *    'my_second_field' => OldCrypt::class
 * ];
 */
class OldCryptJson implements CastsAttributes
{
    private $cryptor = NULL;

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  mixed                               $value
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $this->initCryptor();
        
        $value = $this->cryptor->decrypt($value);

        return json_decode($value, TRUE);
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
        $this->initCryptor();

        if (! is_string($value)) {
            $value = json_encode($value);
        }

        return $this->cryptor->encrypt($value);
    }

    private function initCryptor()
    {
        if (! is_null($this->cryptor)) {
            return;
        }

        $this->cryptor = new Cryptor(config('app.encrypt_decrypt_key'));
    }
}
