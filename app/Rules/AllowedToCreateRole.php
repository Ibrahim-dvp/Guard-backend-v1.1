<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class AllowedToCreateRole implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        if ($user->hasRole('Admin')) {
            return; // Admins can create any role
        }

        if ($user->hasRole('Sales Manager')) {
            if ($value !== 'Sales Agent') {
                $fail("As a Sales Manager, you are only allowed to create users with the 'Sales Agent' role.");
            }
            return;
        }

        // Default deny for any other roles trying to create users
        $fail("You do not have permission to create users with the '{$value}' role.");
    }
}
