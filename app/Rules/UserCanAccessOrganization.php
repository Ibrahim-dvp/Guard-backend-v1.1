<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class UserCanAccessOrganization implements ValidationRule
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
            return; // Admins can access any organization
        }

        if ($user->organization_id !== $value) {
            $fail('You do not have permission to create users in the specified organization.');
        }
    }
}
