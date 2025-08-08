<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserCanAccessOrganization implements ValidationRule
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
        if ($attribute['role_name'] === 'Referral') {
            // $organization = Organization::where('name', 'Group Director')->first();
            // if ($organization) {
            //     $value = $organization->id;
            // }
            return ; // Anyone can create a Referral without organization restriction
        }

        if ($user->hasRole('Admin')) {
            return; // Admins can access any organization
        }

        if ($user->organization_id !== $value) {
            $fail('You do not have permission to create users in the specified organization.');
        }
    }
}
