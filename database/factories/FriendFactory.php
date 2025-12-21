<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Friend;
use App\Models\User;

class FriendFactory extends Factory
{
    protected $model = Friend::class;

    public function definition(): array
    {
        return [
            'user_id_1' => User::factory(),
            'user_id_2' => User::factory(),
            'status' => 'accepted',
            'accepted_at' => now(),
        ];
    }

    /**
     * Pending friend request.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'accepted_at' => null,
        ]);
    }

    /**
     * Accepted friendship.
     */
    public function accepted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Rejected friend request.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'accepted_at' => null,
        ]);
    }

    /**
     * Blocked user.
     */
    public function blocked(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'blocked',
            'accepted_at' => null,
        ]);
    }
}
