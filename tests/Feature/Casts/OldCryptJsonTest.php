<?php

namespace Tests\Feature\Casts;

use Tests\TestCase;
use App\Casts\OldCryptJson;

class OldCryptJsonTest extends TestCase
{
    public function test_old_crypt_json()
    {
        config(['app.encrypt_decrypt_key' => 'test_encryption_key']);

        $original_value = [
            'test_value'  => 'test_value',
            'test_value2' => 'test_value2',
        ];

        $crypt = new OldCryptJson();

        $encrypted_value = $crypt->set(NULL, '', $original_value, []);

        $this->assertTrue(is_string($encrypted_value));

        $decrypted_value = $crypt->get(NULL, '', $encrypted_value, []);

        $this->assertEqualsCanonicalizing($original_value, $decrypted_value);
    }
}
