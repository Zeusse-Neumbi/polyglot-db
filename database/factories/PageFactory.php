<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'category' => fake()->jobTitle(),
            'avatar' => fake()->imageUrl(200, 200, 'business'),
            'owner_id' => User::factory(),
        ];
    }
}
