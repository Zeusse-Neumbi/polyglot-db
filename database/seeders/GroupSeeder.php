<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Database\Seeders\Helpers\SeedingConfig;
use Database\Seeders\Helpers\TimeSimulator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    /**
     * Seed groups with members.
     */
    public function run(): void
    {
        $config = app(SeedingConfig::class);
        $timeSim = app(TimeSimulator::class);

        $limit = $config->getGroupCount();
        $this->command->info("Creating {$limit} Groups with members...");

        $totalUsers = User::count();

        // 1. Create Groups
        $groupsData = [];
        for ($i = 0; $i < $limit; $i++) {
            // Groups created throughout history
            $createdAt = $timeSim->getActivityTimestamp($timeSim->getStartDate(), 0.3); // 0.3 = spread out
            $ownerId = rand(1, $totalUsers);

            // Pass owner_id to prevent factory creation
            $attributes = Group::factory()->raw(['owner_id' => $ownerId]);
            $attributes['created_at'] = $createdAt->toDateTimeString();

            $groupsData[] = $attributes;
        }

        // Chunk insert groups
        foreach (array_chunk($groupsData, 500) as $chunk) {
            Group::insert($chunk);
        }

        // 2. Add Members
        // Each group has 50-500 members? Scale dependent.
        // Small: 5-20. Large: 20-100?
        // Let's keep it reasonable: avg 30 members.

        $this->command->info('Adding members to groups...');

        // Chunk iterate groups
        Group::select('id', 'created_at')->chunkById(500, function ($groups) use ($totalUsers, $timeSim) {
            $membersData = [];

            foreach ($groups as $group) {
                // Determine member count
                // Increased to 30-150 for denser communities
                $memberCount = rand(30, 150);

                $memberIds = [];
                for ($k = 0; $k < $memberCount; $k++) {
                    $memberIds[] = rand(1, $totalUsers);
                }
                $memberIds = array_unique($memberIds); // Dedupe

                $first = true;
                foreach ($memberIds as $userId) {
                    $joinedAt = $timeSim->getActivityTimestamp($group->created_at, 0.5);
                    $role = $first ? 'admin' : (rand(0, 10) > 8 ? 'moderator' : 'member');

                    $membersData[] = [
                        'group_id' => $group->id,
                        'user_id' => $userId,
                        'role' => $role,
                        'joined_at' => $joinedAt->toDateTimeString(),
                    ];
                    $first = false;
                }
            }

            if (! empty($membersData)) {
                foreach (array_chunk($membersData, 1000) as $chunk) {
                    // Using DB facade or model insert
                    // We handle uniqueness per group above.
                    try {
                        DB::table('group_user')->insert($chunk);
                    } catch (\Exception $e) {
                    }
                }
            }
        });

        $this->command->info('Groups seeded.');
    }
}
