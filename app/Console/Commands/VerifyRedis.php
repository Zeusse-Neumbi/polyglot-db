<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class VerifyRedis extends Command
{
    protected $signature = 'verify:redis';

    protected $description = 'Verify Redis integration and counters';

    public function handle()
    {
        $this->info('Starting Redis Verification...');

        // 1. Connection Check
        try {
            Redis::set('test_connection', 'connected');
            $val = Redis::get('test_connection');
            if ($val !== 'connected') {
                $this->error('Redis connection check failed: Value mismatch.');

                return 1;
            }
            Redis::del('test_connection');
            $this->info('✅ Redis Connection OK');
        } catch (\Exception $e) {
            $this->error('Redis connection check failed: '.$e->getMessage());

            return 1;
        }

        // 2. Data Setup
        $this->info('Creating test data...');
        try {
            // Check if users table exists/is accessible
            if (User::count() === 0) {
                $this->warn('Users table might be empty or not found. Attempting create anyway.');
            }

            $user = User::forceCreate([
                'username' => 'redis_test_user_'.Str::random(5),
                'email' => 'redis_test_'.Str::random(5).'@example.com',
                'password' => bcrypt('password'),
                'nom' => 'Test',
                'prenom' => 'User',
            ]);

            $post = Post::create([
                'user_id' => $user->id,
                'content' => 'Redis Test Post',
                'visibility' => 'public',
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to create test data: '.$e->getMessage());

            return 1;
        }

        $likesKey = "post:{$post->id}:likes_count";
        $commentsKey = "post:{$post->id}:comments_count";
        Redis::del($likesKey);
        Redis::del($commentsKey);

        $fail = false;

        // 3. Logic Check
        if ($post->cached_likes_count !== 0) {
            $this->error("❌ Initial Likes count is {$post->cached_likes_count}, expected 0");
            $fail = true;
        }

        // Reaction
        try {
            $reaction = Reaction::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'reaction_type' => 'like',
            ]);

            // Refresh post to ensure it uses fresh instances if logic depends on it (though accessors use Redis direct)
            if ($post->cached_likes_count !== 1) {
                $this->error("❌ Likes count after reaction is {$post->cached_likes_count}, expected 1");
                $fail = true;
            } else {
                $this->info('✅ Likes increment (Redis) OK');
            }

            // Comment
            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'content' => 'Test Comment',
            ]);

            if ($post->cached_comments_count !== 1) {
                $this->error("❌ Comments count after comment is {$post->cached_comments_count}, expected 1");
                $fail = true;
            } else {
                $this->info('✅ Comments increment (Redis) OK');
            }

            // Delete Reaction
            $reaction->delete();
            // In Mongo/Hybrid, make sure relation allows deletion or logic fired.
            if ($post->cached_likes_count !== 0) {
                $this->error("❌ Likes count after delete is {$post->cached_likes_count}, expected 0");
                $fail = true;
            } else {
                $this->info('✅ Likes decrement (delete) OK');
            }

            // Soft Delete Comment
            $comment->delete();
            if ($post->cached_comments_count !== 0) {
                $this->error("❌ Comments count after soft delete is {$post->cached_comments_count}, expected 0");
                $fail = true;
            } else {
                $this->info('✅ Comments decrement (soft delete) OK');
            }

            // Restore Comment
            $comment->restore();
            if ($post->cached_comments_count !== 1) {
                $this->error("❌ Comments count after restore is {$post->cached_comments_count}, expected 1");
                $fail = true;
            } else {
                $this->info('✅ Comments increment (restore) OK');
            }
        } catch (\Exception $e) {
            $this->error('Error during logic verification: '.$e->getMessage());
            $fail = true;
        }

        // Cleanup
        $this->info('Cleaning up...');
        try {
            if (isset($comment)) {
                $comment->forceDelete();
            }
            if (isset($reaction)) {
                $reaction->delete();
            } // might be already deleted
            if (isset($post)) {
                $post->forceDelete();
            }
            if (isset($user)) {
                $user->forceDelete();
            }
        } catch (\Exception $e) {
            $this->warn('Cleanup partial error: '.$e->getMessage());
        }

        Redis::del($likesKey);
        Redis::del($commentsKey);

        if ($fail) {
            $this->error('Verification Failed.');

            return 1;
        }

        $this->info('🎉 All Redis integrations verified successfully!');

        return 0;
    }
}
