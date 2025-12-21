<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seed pages with followers.
     */
    public function run(): void
    {
        $config = app(\Database\Seeders\Helpers\SeedingConfig::class);
        $timeSim = app(\Database\Seeders\Helpers\TimeSimulator::class);

        $limit = $config->getPageCount();
        $this->command->info("Creating {$limit} Pages with followers...");

        $totalUsers = User::count();

        // 1. Create Pages
        $pagesData = [];
        for ($i = 0; $i < $limit; $i++) {
            $createdAt = $timeSim->getActivityTimestamp($timeSim->getStartDate(), 0.4);
            $ownerId = rand(1, $totalUsers);

            // Pass owner_id
            $attributes = Page::factory()->raw(['owner_id' => $ownerId]);
            $attributes['created_at'] = $createdAt->toDateTimeString();

            $pagesData[] = $attributes;
        }

        foreach (array_chunk($pagesData, 500) as $chunk) {
            Page::insert($chunk);
        }

        // 2. Add Followers
        $this->command->info('Adding followers to pages...');

        Page::select('id', 'created_at')->chunkById(500, function ($pages) use ($totalUsers, $timeSim) {
            $followersData = [];

            foreach ($pages as $page) {
                // Pages can be popular
                $followerCount = rand(10, 100);
                if (rand(1, 100) > 90)
                    $followerCount = rand(500, 2000); // Influencer page

                $followerIds = [];
                // If count is huge, picking randoms is slow. 
                // For simplified seeding, just pick loop.
                for ($k = 0; $k < $followerCount; $k++)
                    $followerIds[] = rand(1, $totalUsers);
                $followerIds = array_unique($followerIds);

                foreach ($followerIds as $userId) {
                    $followedAt = $timeSim->getActivityTimestamp($page->created_at, 0.6);

                    $followersData[] = [
                        'page_id' => $page->id,
                        'user_id' => $userId,
                        'notifications_on' => rand(0, 1),
                        'followed_at' => $followedAt->toDateTimeString(),
                    ];
                }
            }

            if (!empty($followersData)) {
                foreach (array_chunk($followersData, 1000) as $chunk) {
                    try {
                        \Illuminate\Support\Facades\DB::table('page_followers')->insert($chunk);
                    } catch (\Exception $e) {
                    }
                }
            }
        });

        $this->command->info('Pages seeded.');
    }
}
