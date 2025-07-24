<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class AllowedToUpdateRole implements DataAwareRule, ValidationRule
{
    /**
     * The data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        // If the user being updated is the same as the authenticated user
        if ($user->id === request()->route('user')->id) {
            if ($user->getRoleNames()->first() !== $value) {
                $fail('You cannot change your own role.');
                return;
            }
            // A user is allowed to "re-submit" their own role without error
            return;
        }

        // If updating a different user, only Admins can change the role.
        if (!$user->hasRole('Admin')) {
            $fail('You do not have permission to change user roles.');
        }
    }
}
