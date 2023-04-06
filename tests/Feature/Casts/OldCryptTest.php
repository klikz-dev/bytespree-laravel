<?php

namespace Tests\Feature\Casts;

use Tests\TestCase;
use App\Casts\OldCrypt;

class OldCryptTest extends TestCase
{
    public function test_old_crypt()
    {
        config(['app.encrypt_decrypt_key' => 'test_encryption_key']);

        $original_value = 'test_value';

        $crypt = new OldCrypt();

        $encrypted_value = $crypt->set(NULL, '', $original_value, []);

        $this->assertNotEquals($original_value, $encrypted_value);

        $decrypted_value = $crypt->get(NULL, '', $encrypted_value, []);

        $this->assertEquals($original_value, $decrypted_value);
    }
}
