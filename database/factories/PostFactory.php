<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'author_type' => User::class,
            'content' => fake()->paragraph(),
            'media_url' => fake()->imageUrl(),
            'media_type' => fake()->randomElement(['image', 'video', 'none']),
            'visibility' => fake()->randomElement(['public', 'friends', 'private']),
            'likes_count' => 0,
            'comments_count' => 0,
        ];
    }
}
