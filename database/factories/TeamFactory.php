<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        
        // Get a random user who can create teams (Sales Manager, Director, etc.)
        $eligibleCreators = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Sales Manager', 'Partner Director', 'Admin']);
        })->get();

        return [
            'name' => ucfirst($name) . ' Team',
            'description' => fake()->paragraph(2),
            'slug' => Str::slug($name) . '-team-' . fake()->randomNumber(3),
            'creator_id' => $eligibleCreators->isNotEmpty() ? $eligibleCreators->random()->id : User::factory(),
        ];
    }

    /**
     * Indicate that the team should have a specific creator.
     *
     * @param User|string $creator
     * @return static
     */
    public function createdBy(User|string $creator): static
    {
        return $this->state(fn (array $attributes) => [
            'creator_id' => $creator instanceof User ? $creator->id : $creator,
        ]);
    }

    /**
     * Indicate that the team should have a specific name and slug.
     *
     * @param string $name
     * @return static
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
