<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Group;
use App\Models\User;

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
