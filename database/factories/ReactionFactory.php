<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        return $this->state(fn (array $attributes) => [
            'post_id' => null,
            'message_id' => Message::factory(),
        ]);
    }

    /**
     * Like reaction.
     */
    public function like(): static
    {
        return $this->state(fn (array $attributes) => [
            'reaction_type' => 'like',
        ]);
    }

    /**
     * Love reaction.
     */
    public function love(): static
    {
        return $this->state(fn (array $attributes) => [
            'reaction_type' => 'love',
        ]);
    }
}
