<?php

namespace Database\Seeders;

use App\Models\Friend;
use App\Models\User;
use Database\Seeders\Helpers\SeedingConfig;
use Database\Seeders\Helpers\TimeSimulator;
use Illuminate\Database\Seeder;

class FriendSeeder extends Seeder
{
    /**
     * Seed the friends table.
     */
    public function run(): void
    {
        $config = app(SeedingConfig::class);
        $timeSim = app(TimeSimulator::class);

        // Configuration
        $totalUsers = User::count(); // Should match config->getUserCount() roughly
        $avgFriends = $config->getAvgFriendsPerUser(); // e.g. 20
        $chunkSize = $config->getChunkSize();

        $this->command->info("Creating Friendships (Target Avg: {$avgFriends}/user)...");
        $bar = $this->command->getOutput()->createProgressBar($totalUsers);
        $bar->start();

        // Processing in chunks to avoid memory issues
        // We will iterate user by user (in chunks) and assign them friends.
        // To avoid duplicates, we only assign friends with ID > current ID?
        // Or simple constraint: Check unique key logic.
        // The table has (user_a, user_b) PK and check constraint user_a < user_b usually
        // Actually schema says: user_a <> user_b. And primary key (user_a, user_b).
        // Best practice: Store smaller ID in user_a, larger in user_b always.

        // Optimization: Friends are mutual. If we loop all users and give them 20 friends, we get double density.
        // Valid for "friend requests sent".
        // Let's iterate 1..$totalUsers.

        $currentId = 1;
        while ($currentId <= $totalUsers) {
            // Get a batch of users to process
            // We need their join dates to set accurate friendship dates
            $users = User::whereBetween('id', [$currentId, $currentId + $chunkSize - 1])
                ->get(['id', 'created_at']);

            $friendships = [];

            foreach ($users as $user) {
                // Determine how many friends this user has (Power law distribution)
                // Most have few, some have many.
                $friendCount = $this->getPowerLawValue($avgFriends, 2, 500);

                // Pick random IDs for friends.
                // We pick from entire population.
                // We only insert if we are the "smaller" ID to avoid duplicates
                // AND to ensure we don't double count connections (A->B and B->A is same row).

                // Wait, if I iterate everyone, I should only add connections to IDs > $user->id
                // to effectively fill the upper triangle of the adjacency matrix.
                // But that biases "old" users to only have "new" friends? No.
                // Correct: "Old" users can have "Old" friends.
                // If I only add B > A, then when I process the last user, I add 0 friends.
                // But the last user might have been added by User 1.
                // So looping A < B is fine distribution-wise IF we consider the whole matrix.
                // Actually, if we want uniformly distributed friends:
                // Node 1 connects to N others. Node N connects to nobody (already connected by others).
                // This might create bias if we are not careful with density.

                // Better approach for huge data:
                // Select K random friends for user A.
                // Normalize pairs so (min, max).
                // Insert ignore to skip dupes.

                $targets = [];
                for ($k = 0; $k < $friendCount; $k++) {
                    $targetId = rand(1, $totalUsers);
                    if ($targetId !== $user->id) {
                        $min = min($user->id, $targetId);
                        $max = max($user->id, $targetId);
                        // Key for simple dedupe in this batch
                        $targets["{$min}-{$max}"] = [$min, $max];
                    }
                }

                foreach ($targets as $pair) {
                    $id1 = $pair[0];
                    $id2 = $pair[1];

                    // We need created_at of the OTHER user to set valid friendship date
                    // Fetching individually is slow. We can estimate or accept slightly imperfect timestamps?
                    // "Real" join date of standard seeded users is predictable by ID:
                    // $timeSim->getUserJoinDate($id, $totalUsers).

                    // We can re-calculate it cheaply!
                    // But admin (ID 1) is special.

                    $targetJoinDate = ($id2 === $user->id)
                        ? $timeSim->getUserJoinDate($id1, $totalUsers) // Calculate other
                        : $timeSim->getUserJoinDate($id2, $totalUsers); // Calculate other

                    // Friendship date = max(join1, join2) + random delay
                    $latestJoin = $user->created_at->max($targetJoinDate);
                    $friendedAt = $timeSim->getActivityTimestamp($latestJoin, 0.5);

                    $status = $this->getRandomStatus();
                    $acceptedAt = ($status === 'accepted') ? $friendedAt : null;

                    $friendships[] = [
                        'user_id_1' => $id1,
                        'user_id_2' => $id2,
                        'status' => $status,
                        'created_at' => $friendedAt->toDateTimeString(),
                        'accepted_at' => $acceptedAt?->toDateTimeString(),
                    ];
                }
            }

            // Insert Ignore/Upsert
            // Laravel insertOrIgnore is great here (requires unique index on pairs)
            if (! empty($friendships)) {
                Friend::insertOrIgnore($friendships);
            }

            $currentId += $chunkSize;
            $bar->advance($users->count());
        }

        $bar->finish();
        $this->command->newLine();
    }

    private function getPowerLawValue($average, $min, $max)
    {
        // Simple approximations
        // 80% have below average, 20% have way above.
        if (rand(1, 100) > 80) {
            return rand($average, $max);
        }

        return rand($min, $average);
    }

    private function getRandomStatus()
    {
        $rand = rand(1, 100);
        if ($rand <= 90) {
            return 'accepted';
        }
        if ($rand <= 95) {
            return 'pending';
        }

        return 'blocked'; // or rejected
    }
}
