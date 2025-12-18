<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Post;

class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'reaction_type' => fake()->randomElement(['like', 'love', 'haha', 'wow', 'sad', 'angry']),
        ];
    }
}
