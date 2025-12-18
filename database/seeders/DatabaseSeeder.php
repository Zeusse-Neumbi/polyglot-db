<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Friend;
use App\Models\Group;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Page;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\SessionAudit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        $this->command->info('Creating Users...');
        $admin = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $users = User::factory(50)->create();
        $users->push($admin);

        // 2. Create Friendships
        $this->command->info('Creating Friendships...');
        foreach ($users as $user) {
            $friends = $users->random(rand(2, 5))->except($user->id);
            foreach ($friends as $friend) {
                // Check if friendship already exists
                $exists = Friend::where(function ($q) use ($user, $friend) {
                    $q->where('user_id_1', $user->id)->where('user_id_2', $friend->id);
                })->orWhere(function ($q) use ($user, $friend) {
                    $q->where('user_id_1', $friend->id)->where('user_id_2', $user->id);
                })->exists();

                if (!$exists) {
                    Friend::factory()->create([
                        'user_id_1' => $user->id,
                        'user_id_2' => $friend->id,
                    ]);
                }
            }
        }

        // 3. Create Posts
        $this->command->info('Creating Posts...');
        $users->each(function ($user) {
            Post::factory(rand(1, 5))->create([
                'user_id' => $user->id,
            ]);
        });

        $posts = Post::all();

        // 4. Create Comments & Reactions
        $this->command->info('Creating Comments and Reactions...');
        $posts->each(function ($post) use ($users) {
            // Reactions on Post
            $reactors = $users->random(rand(0, 10));
            foreach ($reactors as $reactor) {
                if (Reaction::where('post_id', $post->id)->where('user_id', $reactor->id)->doesntExist()) {
                    Reaction::factory()->create([
                        'user_id' => $reactor->id,
                        'post_id' => $post->id,
                        'reaction_type' => fake()->randomElement(['like', 'love', 'haha']),
                    ]);
                }
            }
            $post->update(['likes_count' => $reactors->count()]);

            // Comments on Post
            $commenters = $users->random(rand(0, 5));
            foreach ($commenters as $commenter) {
                $comment = Comment::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $commenter->id,
                ]);
            }

            $post->update(['comments_count' => $commenters->count()]);
        });

        // 5. Create Groups
        $this->command->info('Creating Groups...');
        $groups = Group::factory(10)->create();
        $groups->each(function ($group) use ($users) {
            $members = $users->random(rand(5, 20)); // Ensure we pick valid IDs
            foreach ($members as $member) {
                $group->users()->attach($member->id, [
                    'role' => fake()->randomElement(['member', 'moderator', 'admin']),
                    'joined_at' => now(),
                ]);
            }
        });

        // 6. Create Pages
        $this->command->info('Creating Pages...');
        $pages = Page::factory(8)->create();
        $pages->each(function ($page) use ($users) {
            $followers = $users->random(rand(5, 20));
            foreach ($followers as $follower) {
                $page->followers()->attach($follower->id, [
                    'notifications_on' => fake()->boolean(),
                    'followed_at' => now(),
                ]);
            }
        });

        // 7. Create Conversations & Messages
        $this->command->info('Creating Conversations...');
        // Private conversations
        for ($i = 0; $i < 20; $i++) {
            $participants = $users->random(2);
            $conversation = Conversation::factory()->create(['type' => 'private']);
            $conversation->users()->attach($participants->pluck('id'), ['joined_at' => now()]);

            Message::factory(rand(5, 20))->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $participants->random()->id,
            ]);
        }

        // Group conversations
        for ($i = 0; $i < 5; $i++) {
            $participants = $users->random(rand(3, 8));
            $conversation = Conversation::factory()->create(['type' => 'group']);
            $conversation->users()->attach($participants->pluck('id'), ['joined_at' => now()]);

            Message::factory(rand(10, 50))->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $participants->random()->id,
            ]);
        }

        // 8. Notifications & Session Audits
        $this->command->info('Creating Notifications...');
        $users->each(function ($user) {
            Notification::factory(rand(0, 10))->create(['user_id' => $user->id]);
            SessionAudit::factory(rand(1, 5))->create(['user_id' => $user->id]);
        });

        $this->command->info('Database initialization completed successfully.');
    }
}
