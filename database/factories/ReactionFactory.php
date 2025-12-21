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
            'message_id' => null,
            'reaction_type' => fake()->randomElement(['like', 'love', 'haha', 'wow', 'sad', 'angry']),
        ];
    }

    /**
     * Reaction for a message instead of a post.
     */
    public function forMessage(): static
    {
        return $this->state(fn(array $attributes) => [
            'post_id' => null,
            'message_id' => \App\Models\Message::factory(),
        ]);
    }

    /**
     * Like reaction.
     */
    public function like(): static
    {
        return $this->state(fn(array $attributes) => [
            'reaction_type' => 'like',
        ]);
    }

    /**
     * Love reaction.
     */
    public function love(): static
    {
        return $this->state(fn(array $attributes) => [
            'reaction_type' => 'love',
        ]);
    }
}
