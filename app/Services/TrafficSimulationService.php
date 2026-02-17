<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Friend;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Page;
use App\Models\Reaction;
use App\Models\Notification;
use App\Models\SessionAudit;
use Database\Seeders\Helpers\SeedingConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrafficSimulationService
{
    protected int $actionCount = 0;

    // Reaction distribution based on research (Like 70%, Love 15%, etc.)
    protected array $reactionWeights = [
        'like' => 70,
        'love' => 15,
        'haha' => 8,
        'wow' => 4,
        'sad' => 2,
        'angry' => 1,
    ];

    /**
     * Run the simulation with realistic patterns.
     */
    public function runSimulation(int $durationSeconds, int $activePercent): int
    {
        $totalUsers = User::count();
        $activeCount = max(1, (int) ceil($totalUsers * ($activePercent / 100)));

        // Select users and assign personas based on power-law (90-9-1)
        $allActive = User::inRandomOrder()->take($activeCount)->get();
        $usersWithPersonas = $this->assignPersonas($allActive);

        if ($allActive->isEmpty()) {
            Log::info('No users found for simulation.');
            return 0;
        }

        Log::info("Simulation started: {$activeCount} users for {$durationSeconds}s (Power-law distribution).");

        // Login all active users
        foreach ($allActive as $user) {
            $this->loginUser($user);
        }

        $endTime = time() + $durationSeconds;
        $this->actionCount = 0;

        // Main loop with session-based bursts
        while (time() < $endTime) {
            foreach ($usersWithPersonas as $userData) {
                if (time() >= $endTime)
                    break;

                $user = $userData['user'];
                $persona = $userData['persona'];
                $actionsPerBurst = $userData['actions_per_burst'];

                // Perform burst of actions based on persona
                for ($i = 0; $i < $actionsPerBurst && time() < $endTime; $i++) {
                    try {
                        $this->performRandomAction($user, $persona);
                    } catch (\Exception $e) {
                        Log::error("Sim Error User {$user->id}: " . $e->getMessage());
                    }
                }
            }
        }

        // Logout all active users
        foreach ($allActive as $user) {
            $this->logoutUser($user);
        }

        Log::info("Simulation ended. Total actions: {$this->actionCount}");
        return $this->actionCount;
    }

    /**
     * Assign personas based on power-law (90-9-1 rule).
     */
    protected function assignPersonas($users): array
    {
        $result = [];
        $total = $users->count();

        foreach ($users as $index => $user) {
            $percentile = ($index / max($total, 1)) * 100;

            if ($percentile < 1) {
                // 1% Influencers - extremely active
                $persona = 'influencer';
                $actionsPerBurst = rand(30, 50);
            } elseif ($percentile < 10) {
                // 9% Power Users
                $persona = 'power_user';
                $actionsPerBurst = rand(15, 30);
            } elseif ($percentile < 30) {
                // 20% Active Users
                $persona = 'active';
                $actionsPerBurst = rand(5, 15);
            } elseif ($percentile < 60) {
                // 30% Casual Users
                $persona = 'casual';
                $actionsPerBurst = rand(2, 5);
            } else {
                // 40% Lurkers - mostly read
                $persona = 'lurker';
                $actionsPerBurst = rand(0, 2);
            }

            $result[] = [
                'user' => $user,
                'persona' => $persona,
                'actions_per_burst' => $actionsPerBurst,
            ];
        }

        return $result;
    }

    /**
     * Perform action based on persona weights.
     */
    protected function performRandomAction(User $user, string $persona): void
    {
        $dice = rand(1, 100);

        // Lurkers mostly read (consume content)
        if ($persona === 'lurker') {
            if ($dice <= 60) {
                $this->readNotifications($user);
            } elseif ($dice <= 85) {
                $this->markMessagesRead($user);
            } elseif ($dice <= 95) {
                $this->reactToPost($user, true); // Prefer viral content
            } else {
                $this->commentOnPost($user);
            }
            return;
        }

        // Influencers create more content
        if ($persona === 'influencer') {
            if ($dice <= 25) {
                $this->createPost($user);
            } elseif ($dice <= 40) {
                $this->reactToPost($user, true);
            } elseif ($dice <= 55) {
                $this->commentOnPost($user);
            } elseif ($dice <= 65) {
                $this->sendMessage($user);
            } elseif ($dice <= 75) {
                $this->handleFriendAction($user);
            } elseif ($dice <= 82) {
                $this->postInGroup($user);
            } elseif ($dice <= 89) {
                $this->postAsPage($user);
            } elseif ($dice <= 94) {
                $this->handleGroupAction($user);
            } else {
                $this->handlePageAction($user);
            }
            return;
        }

        // Standard distribution for power_user, active, casual
        if ($dice <= 8) {
            $this->createPost($user);
        } elseif ($dice <= 25) {
            $this->reactToPost($user, true);
        } elseif ($dice <= 40) {
            $this->commentOnPost($user);
        } elseif ($dice <= 52) {
            $this->handleFriendAction($user);
        } elseif ($dice <= 65) {
            $this->sendMessage($user);
        } elseif ($dice <= 72) {
            $this->handleGroupAction($user);
        } elseif ($dice <= 79) {
            $this->handlePageAction($user);
        } elseif ($dice <= 84) {
            $this->readNotifications($user);
        } elseif ($dice <= 89) {
            $this->markMessagesRead($user);
        } elseif ($dice <= 93) {
            $this->updateProfile($user);
        } elseif ($dice <= 96) {
            $this->postInGroup($user);
        } else {
            $this->unlikePost($user);
        }
    }

    /**
     * Get weighted random reaction type.
     */
    protected function getWeightedReaction(): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($this->reactionWeights as $type => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $type;
            }
        }

        return 'like';
    }

    // ========== CORE ACTIONS ==========

    protected function createPost(User $user): void
    {
        Post::create([
            'author_id' => $user->id,
            'author_type' => User::class,
            'content' => SeedingConfig::generatePostContent(),
            'visibility' => ['public', 'friends', 'private'][rand(0, 2)],
            'likes_count' => 0,
            'comments_count' => 0,
        ]);
        $this->actionCount++;
        Log::debug("User {$user->id} created post");
    }

    protected function reactToPost(User $user, bool $preferViral = false): void
    {
        // Prefer posts with higher engagement (viral cascade simulation)
        $post = $preferViral
            ? Post::orderBy('likes_count', 'desc')->take(100)->get()->random()
            : Post::inRandomOrder()->first();

        if (!$post)
            return;

        $exists = Reaction::where('user_id', $user->id)->where('post_id', $post->id)->exists();
        if (!$exists) {
            Reaction::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'reaction_type' => $this->getWeightedReaction(),
            ]);
            $this->actionCount++;
            Log::debug("User {$user->id} reacted to post");
        }
    }

    protected function commentOnPost(User $user): void
    {
        // Prefer engaging posts
        $post = Post::orderBy('comments_count', 'desc')->take(50)->get()->random();
        if (!$post)
            $post = Post::inRandomOrder()->first();
        if (!$post)
            return;

        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => SeedingConfig::generateMessageContent(),
        ]);
        $this->actionCount++;
        Log::debug("User {$user->id} commented on post");
    }

    protected function handleFriendAction(User $user): void
    {
        if (rand(0, 1)) {
            $pending = Friend::where('user_id_2', $user->id)->where('status', 'pending')->first();
            if ($pending) {
                $pending->update(['status' => 'accepted', 'accepted_at' => now()]);
                $this->actionCount++;
                Log::debug("User {$user->id} accepted friend request");
                return;
            }
        }

        $target = User::inRandomOrder()->where('id', '!=', $user->id)->first();
        if (!$target)
            return;

        $min = min($user->id, $target->id);
        $max = max($user->id, $target->id);

        $exists = Friend::where('user_id_1', $min)->where('user_id_2', $max)->exists();
        if (!$exists) {
            Friend::create(['user_id_1' => $min, 'user_id_2' => $max, 'status' => 'pending']);
            $this->actionCount++;
            Log::debug("User {$user->id} sent friend request");
        }
    }

    protected function sendMessage(User $user): void
    {
        $conversation = $user->conversations()->inRandomOrder()->first();

        if (!$conversation) {
            $target = User::inRandomOrder()->where('id', '!=', $user->id)->first();
            if (!$target)
                return;

            $conversation = Conversation::create(['type' => 'private']);
            DB::table('conversation_user')->insert([
                ['conversation_id' => $conversation->id, 'user_id' => $user->id, 'joined_at' => now(), 'role' => 'member'],
                ['conversation_id' => $conversation->id, 'user_id' => $target->id, 'joined_at' => now(), 'role' => 'member'],
            ]);
            $this->actionCount++;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => SeedingConfig::generateMessageContent(),
            'attachments' => rand(1, 100) <= 10 ? [['type' => 'image', 'url' => 'https://picsum.photos/400']] : [],
            'is_read' => false,
        ]);
        $this->actionCount++;
        Log::debug("User {$user->id} sent message");
    }

    protected function handleGroupAction(User $user): void
    {
        if (rand(1, 100) <= 10) {
            $group = Group::create([
                'owner_id' => $user->id,
                'name' => fake()->company . ' Group',
                'description' => fake()->catchPhrase,
                'visibility' => 'public',
                'avatar' => null,
            ]);
            DB::table('group_user')->insert([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);
            $this->actionCount++;
            Log::debug("User {$user->id} created group");
        } else {
            $group = Group::inRandomOrder()->first();
            if ($group && !$group->users()->where('user_id', $user->id)->exists()) {
                DB::table('group_user')->insert([
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
                $this->actionCount++;
                Log::debug("User {$user->id} joined group");
            }
        }
    }

    protected function handlePageAction(User $user): void
    {
        if (rand(1, 100) <= 8) {
            $page = Page::create([
                'owner_id' => $user->id,
                'name' => fake()->company,
                'description' => fake()->bs,
                'category' => ['Business', 'Entertainment', 'Tech', 'Sports'][rand(0, 3)],
                'avatar' => null,
            ]);
            $this->actionCount++;
            Log::debug("User {$user->id} created page");
        } else {
            $page = Page::inRandomOrder()->first();
            if ($page && !$page->followers()->where('user_id', $user->id)->exists()) {
                DB::table('page_followers')->insert([
                    'page_id' => $page->id,
                    'user_id' => $user->id,
                    'notifications_on' => rand(0, 1),
                    'followed_at' => now(),
                ]);
                $this->actionCount++;
                Log::debug("User {$user->id} followed page");
            }
        }
    }

    // ========== SECONDARY ACTIONS ==========

    protected function loginUser(User $user): void
    {
        SessionAudit::create([
            'user_id' => $user->id,
            'session_token' => Str::random(40),
            'ip_address' => fake()->ipv4,
            'device' => ['desktop', 'mobile', 'tablet'][rand(0, 2)],
            'user_agent' => fake()->userAgent,
            'login_at' => now(),
            'logout_at' => null,
        ]);
    }

    protected function logoutUser(User $user): void
    {
        $session = SessionAudit::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->orderBy('login_at', 'desc')
            ->first();

        if ($session) {
            $session->update(['logout_at' => now()]);
        }
    }

    protected function readNotifications(User $user): void
    {
        $unread = Notification::where('user_id', $user->id)->where('is_read', false)->take(5)->get();
        foreach ($unread as $notif) {
            $notif->update(['is_read' => true]);
            $this->actionCount++;
        }
    }

    protected function markMessagesRead(User $user): void
    {
        $conversation = $user->conversations()->inRandomOrder()->first();
        if (!$conversation)
            return;

        $messages = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->take(10)
            ->get();

        foreach ($messages as $message) {
            $message->update(['is_read' => true]);
            $this->actionCount++;
        }
    }

    protected function updateProfile(User $user): void
    {
        $updates = [];
        $field = rand(1, 3);

        if ($field === 1) {
            $updates['bio'] = fake()->sentence;
        } elseif ($field === 2) {
            $updates['profile_photo'] = 'https://i.pravatar.cc/150?u=' . $user->id;
        } else {
            $updates['cover_photo'] = 'https://picsum.photos/800/300?random=' . rand(1, 1000);
        }

        $user->update($updates);
        $this->actionCount++;
        Log::debug("User {$user->id} updated profile");
    }

    protected function unlikePost(User $user): void
    {
        $reaction = Reaction::where('user_id', $user->id)->inRandomOrder()->first();
        if ($reaction) {
            $reaction->delete();
            $this->actionCount++;
        }
    }

    protected function postInGroup(User $user): void
    {
        $membership = DB::table('group_user')->where('user_id', $user->id)->inRandomOrder()->first();
        if (!$membership)
            return;

        $group = Group::find($membership->group_id);
        if (!$group)
            return;

        Post::create([
            'author_id' => $user->id,
            'author_type' => User::class,
            'content' => "[Group: {$group->name}] " . SeedingConfig::generatePostContent(),
            'visibility' => 'group',
            'likes_count' => 0,
            'comments_count' => 0,
        ]);
        $this->actionCount++;
        Log::debug("User {$user->id} posted in group");
    }

    protected function postAsPage(User $user): void
    {
        $page = Page::where('owner_id', $user->id)->inRandomOrder()->first();
        if (!$page)
            return;

        Post::create([
            'author_id' => $page->id,
            'author_type' => Page::class,
            'content' => SeedingConfig::generatePostContent(),
            'visibility' => 'public',
            'likes_count' => 0,
            'comments_count' => 0,
        ]);
        $this->actionCount++;
        Log::debug("Page {$page->id} posted");
    }
}
