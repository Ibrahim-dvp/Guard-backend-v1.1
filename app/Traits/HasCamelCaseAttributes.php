<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasCamelCaseAttributes
{
    /**
     * The attributes that should be accessible in camelCase.
     * Override this in your model to specify which attributes to transform.
     *
     * @var array
     */
    protected $camelCaseAttributes = [];

    /**
     * Get an attribute from the model with camelCase support.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        // If the key is in camelCase, convert to snake_case
        if (in_array($key, $this->getCamelCaseAttributes()) || $this->shouldConvertToCamelCase($key)) {
            $snakeKey = Str::snake($key);
            if ($this->hasAttribute($snakeKey) || $this->hasGetMutator($snakeKey)) {
                return parent::getAttribute($snakeKey);
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Set a given attribute on the model with camelCase support.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // If the key is in camelCase, convert to snake_case
        if (in_array($key, $this->getCamelCaseAttributes()) || $this->shouldConvertToCamelCase($key)) {
            $snakeKey = Str::snake($key);
            if ($this->hasAttribute($snakeKey) || $this->hasSetMutator($snakeKey)) {
                return parent::setAttribute($snakeKey, $value);
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get the camelCase attributes for this model.
     *
     * @return array
     */
    protected function getCamelCaseAttributes(): array
    {
        return array_merge($this->camelCaseAttributes, [
            'firstName', 'lastName', 'organizationId', 'createdBy', 'createdAt', 'updatedAt',
            'clientFirstName', 'clientLastName', 'clientEmail', 'clientPhone', 'clientCompany',
            'assignedTo', 'assignedBy', 'referralId', 'parentId', 'directorId', 'isActive',
            'scheduledAt', 'scheduledBy', 'leadId', 'creatorId', 'usersCount', 'activeUsersCount',
            'isInternal'
        ]);
    }

    /**
     * Determine if we should convert this key to camelCase.
     *
     * @param  string  $key
     * @return bool
     */
    protected function shouldConvertToCamelCase(string $key): bool
    {
        // Convert if the key is camelCase and doesn't already exist as-is
        return Str::camel($key) === $key 
            && $key !== Str::snake($key) 
            && !$this->hasAttribute($key);
    }

    /**
     * Convert the model's attributes to an array with camelCase keys for API responses.
     *
     * @return array
     */
    public function toCamelCaseArray(): array
    {
        $attributes = $this->toArray();
        $camelCaseAttributes = [];

        foreach ($attributes as $key => $value) {
            $camelKey = Str::camel($key);
            $camelCaseAttributes[$camelKey] = $value;
        }

        return $camelCaseAttributes;
    }
}
