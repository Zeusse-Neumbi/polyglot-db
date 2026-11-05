<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Page;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Database\Seeders\Helpers\SeedingConfig;
use Database\Seeders\Helpers\TimeSimulator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    /**
     * Seed posts, comments, and reactions.
     */
    public function run(): void
    {
        $config = app(SeedingConfig::class);
        $timeSim = app(TimeSimulator::class);

        $chunkSize = $config->getChunkSize();

        // --- PHASE 1: CREATE POSTS ---
        $this->command->info('Creating Posts...');

        $totalUsers = User::count();
        $bar = $this->command->getOutput()->createProgressBar($totalUsers);
        $bar->start();

        // Create Posts for Users
        $this->command->info('Creating User Posts...');
        $currentId = 1;
        while ($currentId <= $totalUsers) {
            $users = User::whereBetween('id', [$currentId, $currentId + $chunkSize - 1])
                ->get(['id', 'created_at']);

            $postsData = [];
            foreach ($users as $user) {
                // Use persona-based post counts for consistency
                $persona = SeedingConfig::getUserPersona($user->id);
                $postRanges = $config->getPostsPerUser();
                $range = $postRanges[$persona] ?? [0, 0];
                $postCount = rand($range[0], $range[1]);

                for ($k = 0; $k < $postCount; $k++) {
                    $createdAt = $timeSim->getActivityTimestamp($user->created_at, 0.6);

                    // SKIP FACTORY OVERHEAD - Direct array generation
                    $postsData[] = [
                        'author_id' => $user->id,
                        'author_type' => User::class,
                        'content' => SeedingConfig::generatePostContent(),
                        'media' => null,
                        'visibility' => 'public',
                        'likes_count' => 0,
                        'comments_count' => 0,
                        'created_at' => $createdAt->toDateTimeString(),
                        'updated_at' => $createdAt->toDateTimeString(),
                    ];
                }

                // Memory management
                if (count($postsData) >= 1000) {
                    gc_collect_cycles();
                }
            }

            // WRAP IN TRANSACTION for faster writes
            if (! empty($postsData)) {
                DB::transaction(function () use ($postsData) {
                    foreach (array_chunk($postsData, 500) as $chunk) {
                        Post::insert($chunk);
                    }
                });
            }
            $currentId += $chunkSize;
            $bar->advance($users->count());
        }
        $bar->finish();
        $this->command->newLine();

        // Create Posts for Pages
        $this->command->info('Creating Page Posts...');
        $totalPages = Page::count();
        if ($totalPages > 0) {
            $currentId = 1;
            while ($currentId <= $totalPages) {
                $pages = Page::whereBetween('id', [$currentId, $currentId + $chunkSize - 1])
                    ->get(['id', 'created_at']);

                $postsData = [];
                foreach ($pages as $page) {
                    // Pages post frequently
                    $postCount = rand(50, 150);

                    for ($k = 0; $k < $postCount; $k++) {
                        $createdAt = $timeSim->getActivityTimestamp($page->created_at, 0.8);

                        // SKIP FACTORY OVERHEAD - Direct array generation
                        $postsData[] = [
                            'author_id' => $page->id,
                            'author_type' => Page::class,
                            'content' => SeedingConfig::generatePostContent(),
                            'media' => null,
                            'visibility' => 'public',
                            'likes_count' => 0,
                            'comments_count' => 0,
                            'created_at' => $createdAt->toDateTimeString(),
                            'updated_at' => $createdAt->toDateTimeString(),
                        ];
                    }
                }
                // WRAP IN TRANSACTION for faster writes
                if (! empty($postsData)) {
                    DB::transaction(function () use ($postsData) {
                        foreach (array_chunk($postsData, 500) as $chunk) {
                            Post::insert($chunk);
                        }
                    });
                }
                $currentId += $chunkSize;
            }
        }
        $bar->finish();
        $this->command->newLine();

        // --- PHASE 2: ENGAGEMENT (COMMENTS & REACTIONS) ---
        // Iterate over POSTS now

        $this->command->info('Generating Engagement (Comments & Reactions)...');
        $totalPosts = Post::count();
        $barPosts = $this->command->getOutput()->createProgressBar($totalPosts);
        $barPosts->start();

        // We chunk by ID again
        // Note: ID-based chunking is safe as we just inserted them
        // Reduced to 200 to prevent OOM with high engagement rates
        Post::select('id', 'created_at')->chunkById(200, function ($posts) use ($timeSim, $totalUsers, $barPosts) {
            $commentsData = [];
            $reactionsData = [];

            // Pre-calculate random users for this batch to save rand calls?
            // Better: just pick random IDs.

            foreach ($posts as $post) {
                // 1. Reactions
                // 30% posts have 0 likes, 10% viral.
                $reactionCount = $this->getReactionCount();

                // Generate unique reactors
                $reactorIds = [];
                for ($i = 0; $i < $reactionCount; $i++) {
                    $reactorIds[] = rand(1, $totalUsers);
                }
                $reactorIds = array_unique($reactorIds); // simple dedupe

                foreach ($reactorIds as $reactorId) {
                    // Start timestamp
                    $reactedAt = $timeSim->getActivityTimestamp($post->created_at, 0.7);

                    $reactionsData[] = [
                        'post_id' => $post->id,
                        'user_id' => $reactorId,
                        'reaction_type' => FakerPool::randomReaction(),
                        'created_at' => $reactedAt->toDateTimeString(),
                    ];
                }

                // 2. Comments (Duplicates allowed)
                $commentCount = $this->getCommentCount();
                for ($i = 0; $i < $commentCount; $i++) {
                    $commenterId = rand(1, $totalUsers);
                    $commentedAt = $timeSim->getActivityTimestamp($post->created_at, 0.8);

                    $commentsData[] = [
                        'post_id' => $post->id,
                        'user_id' => $commenterId,
                        'content' => FakerPool::randomSentence(),
                        'created_at' => $commentedAt->toDateTimeString(),
                        'updated_at' => $commentedAt->toDateTimeString(),
                    ];
                }
            }

            // Insert in chunks
            // Use standard insert() as we handled uniqueness above (mostly)
            // Note: If DB has unique constraint and we happen to pick same user for same post in a different batch?
            // We are processing posts by ID. We handle ALL reactions for ONE post here.
            // So PHP dedupe is sufficient.

            if (! empty($reactionsData)) {
                foreach (array_chunk($reactionsData, 1000) as $chunk) {
                    try {
                        Reaction::insert($chunk);
                    } catch (\Exception $e) {
                        // Fallback or ignore
                    }
                }
            }

            if (! empty($commentsData)) {
                foreach (array_chunk($commentsData, 1000) as $chunk) {
                    Comment::insert($chunk);
                }
            }

            $barPosts->advance($posts->count());
        });

        $barPosts->finish();
        $this->command->newLine();
        $this->command->info('Created comments and reactions.');

        // --- PHASE 3: UPDATE ENGAGEMENT COUNTS (BULK) ---
        $this->command->info('Updating post engagement counts (bulk aggregation)...');

        $totalPosts = Post::count();
        $bar3 = $this->command->getOutput()->createProgressBar($totalPosts);
        $bar3->start();

        // Use chunked updates with direct counting for performance
        Post::chunkById(500, function ($posts) use ($bar3) {
            $postIds = $posts->pluck('_id')->toArray();

            // Batch count reactions for all posts in this chunk
            $reactionCounts = Reaction::whereIn('post_id', $postIds)
                ->get()
                ->groupBy('post_id')
                ->map->count();

            // Batch count comments for all posts in this chunk
            $commentCounts = Comment::whereIn('post_id', $postIds)
                ->get()
                ->groupBy('post_id')
                ->map->count();

            foreach ($posts as $post) {
                $post->update([
                    'likes_count' => $reactionCounts->get($post->_id, 0),
                    'comments_count' => $commentCounts->get($post->_id, 0),
                ]);
            }

            $bar3->advance($posts->count());
            gc_collect_cycles();
        });

        $bar3->finish();
        $this->command->newLine();
        $this->command->info('Post counts updated.');
    }

    private function getReactionCount(): int
    {
        $rand = rand(1, 100);
        if ($rand > 90) {
            return rand(100, 300);
        } // 10% Viral (High)
        if ($rand > 60) {
            return rand(20, 80);
        }   // 30% Popular
        if ($rand > 20) {
            return rand(5, 20);
        }    // 40% Standard

        return 0; // 20% Ignored
    }

    private function getCommentCount(): int
    {
        $rand = rand(1, 100);
        if ($rand > 95) {
            return rand(30, 80);
        } // 5% Viral
        if ($rand > 75) {
            return rand(10, 30);
        } // 20% Popular
        if ($rand > 40) {
            return rand(3, 10);
        }  // 35% Active

        return 0;
    }
}
