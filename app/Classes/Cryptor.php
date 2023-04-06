<?php

namespace App\Classes;

/**
 * Class description
 */
class Cryptor
{
    protected $method = 'aes-128-ctr'; // default cipher method if none supplied
    protected $soft_method = 'bf-ecb';
    private $key;

    protected function iv_bytes()
    {
        return openssl_cipher_iv_length($this->method);
    }

    public function __construct($key = FALSE, $method = FALSE)
    {
        if ($key !== FALSE) {
            $this->key = $key;
        } else {
            $this->key = config("app.encrypt_decrypt_key");
        }
       
        if ($method) {
            if (in_array(strtolower($method), openssl_get_cipher_methods())) {
                $this->method = $method;
            } else {
                exit(__METHOD__ . ": unrecognised cipher method: {$method}");
            }
        }
    }

    /**
     * Encrypt a strong
     *
     * @param  string $data
     * @param  bool   $use_ecb Use a soft encryption method
     * @return void
     */
    public function encrypt($data, $use_ecb = FALSE)
    {
        if ($this->key === FALSE) {
            return $data;
        }

        if ($use_ecb) {
            return openssl_encrypt($data, $this->soft_method, $this->key);
        }

        $iv = openssl_random_pseudo_bytes($this->iv_bytes());

        return bin2hex($iv) . openssl_encrypt($data, $this->method, $this->key, 0, $iv);
    }

    /**
     * Decrypt a string
     *
     * @param  string $data    Data to be decrypted
     * @param  bool   $use_ecb Whether or not a soft method should be used
     * @return mixed
     */
    public function decrypt($data, $use_ecb = FALSE)
    {
        if ($this->key === FALSE) {
            return $data;
        }
        
        if ($use_ecb) {
            return openssl_decrypt($data, $this->soft_method, $this->key);
        }

        $iv_strlen = 2 * $this->iv_bytes();
        if (preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
            list(, $iv, $data) = $regs;
            if (ctype_xdigit($iv) && strlen($iv) % 2 == 0) {
                return openssl_decrypt($data, $this->method, $this->key, 0, hex2bin($iv));
            }
        }

        return FALSE; // failed to decrypt
    }
}
