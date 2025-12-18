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
            // Ensure user_id_2 is different in seeder logic or here if possible, but distinct is hard in factory alone without closure
            'user_id_2' => User::factory(),
            'status' => 'accepted',
            'accepted_at' => now(),
        ];
    }
}
