<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Notification;
use App\Models\User;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['friend_request', 'new_post', 'new_comment', 'reaction']),
            'data' => json_encode(['message' => fake()->sentence()]),
            'read_at' => fake()->boolean() ? now() : null,
        ];
    }
}
