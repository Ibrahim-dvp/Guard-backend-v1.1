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

        // Get the role_name from the request data
        $request = request();
        $roleName = $request->input('role_name');
        
        if ($roleName === 'Referral' || $user->hasRole(['Admin', 'Group Director'])) {
            return; // Anyone can create a Referral without organization restriction
        }

        if ($user->organization_id !== $value) {
            $fail('You do not have permission to create users in the specified organization.');
        }
    }
}
