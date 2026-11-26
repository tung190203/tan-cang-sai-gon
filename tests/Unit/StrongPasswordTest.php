<?php

namespace Tests\Unit;

use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StrongPasswordTest extends TestCase
{
    public function test_strong_password_passes(): void
    {
        $validator = Validator::make(
            ['password' => 'Tc@2026#StrongPass!9'],
            ['password' => [new StrongPassword()]]
        );

        $this->assertFalse($validator->fails());
    }

    /**
     * @dataProvider weakPasswordsProvider
     */
    public function test_weak_passwords_fail(string $password): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => [new StrongPassword()]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function weakPasswordsProvider(): array
    {
        return [
            'too short' => ['Ab1!xyz'],
            'missing uppercase' => ['abcdef1!'],
            'missing lowercase' => ['ABCDEF1!'],
            'missing number' => ['Abcdefg!'],
            'missing special char' => ['Abcdefg1'],
        ];
    }
}
