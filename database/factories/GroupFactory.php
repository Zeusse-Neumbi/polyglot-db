<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'visibility' => fake()->randomElement(['public', 'private']),
            'avatar' => fake()->imageUrl(200, 200, 'abstract'),
            'owner_id' => User::factory(),
        ];
    }
}
