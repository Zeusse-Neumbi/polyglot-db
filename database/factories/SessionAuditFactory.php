<?php

namespace Database\Factories;

use App\Models\SessionAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionAuditFactory extends Factory
{
    protected $model = SessionAudit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Note: SessionAudit is MongoDB, so user_id might be reference or int depending on schema. Assuming User ID int.
            'session_token' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'device' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'user_agent' => fake()->userAgent(),
            'login_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'logout_at' => fake()->boolean() ? fake()->dateTimeBetween('-1 month', 'now') : null,
        ];
    }
}
