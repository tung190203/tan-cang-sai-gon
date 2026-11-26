<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Validate that the password is at least 8 characters and contains
     * uppercase, lowercase, numeric, and special characters.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Mật khẩu không hợp lệ.');

            return;
        }

        $hasMinLength = mb_strlen($value) >= 8;
        $hasUpper = preg_match('/[A-Z]/', $value) === 1;
        $hasLower = preg_match('/[a-z]/', $value) === 1;
        $hasDigit = preg_match('/[0-9]/', $value) === 1;
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $value) === 1;

        if (! ($hasMinLength && $hasUpper && $hasLower && $hasDigit && $hasSpecial)) {
            $fail('Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.');
        }
    }
}
