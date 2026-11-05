<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->sentence(),
            'attachments' => fake()->boolean(20) ? [
                ['type' => 'image', 'url' => fake()->imageUrl()],
            ] : null,
            'is_read' => fake()->boolean(),
        ];
    }

    /**
     * Indicate that the message is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    /**
     * Indicate that the message has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Indicate that the message has an attachment.
     */
    public function withAttachment(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => [
                ['type' => fake()->randomElement(['image', 'video', 'file']), 'url' => fake()->url()],
            ],
        ]);
    }
}
