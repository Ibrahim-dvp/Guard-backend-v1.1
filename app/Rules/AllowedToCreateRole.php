<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class AllowedToCreateRole implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($value === 'Referral') {
            return; // Anyone can create a Referral
        }

        if ($user->hasRoles('Admin', 'Group Director')) {
            return; // Admins can create any role
        }

        if ($user->hasRole('Partner Director')) {
            if (!in_array($value, ['Sales Manager', 'Sales Agent'])) {
                $fail("As a Partner Director, you can only create Sales Managers or Sales Agents.");
            }
            return;
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
