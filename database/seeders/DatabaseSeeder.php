<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Social Network Data Seeder');

        // Check for existing checkpoint
        $checkpoint = \Database\Seeders\Helpers\SeedingCheckpoint::class;
        if ($checkpoint::exists()) {
            $info = $checkpoint::getInfo();
            $this->command->warn("⚠️  {$info}");
            if ($this->command->confirm('Do you want to resume from this checkpoint?', true)) {
                // Resume logic would be handled by individual seeders
                $this->command->info('Resuming from checkpoint...');
            } else {
                $checkpoint::clear();
                $this->command->info('Checkpoint cleared. Starting fresh.');
            }
        }

        // 1. Choose Scale
        $scale = $this->chooseScale();
        $config = new \Database\Seeders\Helpers\SeedingConfig($scale);
        $timeSim = new \Database\Seeders\Helpers\TimeSimulator();

        $this->command->info("📊 Configuration: {$config->getScaleLabel()}");
        $this->command->info("👥 Target Users: " . number_format($config->getUserCount()));
        $this->command->info("📅 Historical Data: 5 Years ({$timeSim->getStartDate()->toDateString()} to Now)");

        if (!$this->command->confirm('Do you want to proceed?', true)) {
            $this->command->warn('Seeding aborted.');
            return;
        }

        $this->command->newLine();
        $startTime = microtime(true);

        // ========== PERFORMANCE OPTIMIZATIONS ==========
        $this->command->info('⚡ Enabling performance optimizations...');

        // 1. Disable query logging (saves memory)
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // 2. Disable Eloquent events for all models during seeding
        \Illuminate\Database\Eloquent\Model::unguard();

        // 3. Pre-generate faker data pools for reuse
        $this->generateFakerPools();

        // Make config available to other seeders via Singleton
        app()->instance(\Database\Seeders\Helpers\SeedingConfig::class, $config);
        app()->instance(\Database\Seeders\Helpers\TimeSimulator::class, $timeSim);

        $this->call([
            UserSeeder::class,
            GroupSeeder::class,
            PageSeeder::class,
            FriendSeeder::class,
            PostSeeder::class,
            ConversationSeeder::class,
            NotificationSeeder::class,
        ]);

        // ========== RESTORE DEFAULTS ==========
        \Illuminate\Database\Eloquent\Model::reguard();

        // Clear checkpoint on successful completion
        $checkpoint::clear();

        $duration = round(microtime(true) - $startTime, 2);
        $this->command->newLine();
        $this->command->info("✅ Database seeding completed in {$duration}s!");
    }

    private function chooseScale(): string
    {
        // If passed as argument (future improvement), read it here.
        // For now, interactive only.

        $scales = \Database\Seeders\Helpers\SeedingConfig::getAvailableScales();
        $options = [];
        $keyMap = [];

        foreach ($scales as $key => $details) {
            $options[] = $details['label'];
            $keyMap[] = $key; // map index to key
        }

        $choiceIndex = array_search(
            $this->command->choice('Select Seeding Scale', $options, 0),
            $options
        );

        return $keyMap[$choiceIndex];
    }

    /**
     * Pre-generate pools of faker data to avoid repeated faker calls.
     * This significantly speeds up seeding by reusing generated data.
     */
    private function generateFakerPools(): void
    {
        $poolSize = 1000;

        // Store in a static class for access by all seeders
        FakerPool::$sentences = array_map(fn() => fake()->sentence(), range(1, $poolSize));
        FakerPool::$paragraphs = array_map(fn() => fake()->paragraph(), range(1, $poolSize));
        FakerPool::$words = array_map(fn() => fake()->words(3, true), range(1, $poolSize));
        FakerPool::$names = array_map(fn() => fake()->name(), range(1, $poolSize));
        FakerPool::$emails = array_map(fn() => fake()->unique()->safeEmail(), range(1, $poolSize));
        FakerPool::$imageUrls = array_map(fn() => fake()->imageUrl(640, 480), range(1, 200));
        FakerPool::$reactions = ['like', 'like', 'like', 'love', 'haha', 'wow', 'sad', 'angry'];
        FakerPool::$shortMessages = ['ok', 'lol', '👍', '😂', 'nice!', 'cool', 'thanks!', '❤️', 'wow', 'haha', 'yes', 'no', '🔥', '👏'];

        $this->command->info('  ✓ Pre-generated ' . ($poolSize * 5) . ' faker data items');
    }
}

/**
 * Static class to hold pre-generated faker data pools.
 * Accessible from any seeder for fast random data selection.
 */
class FakerPool
{
    public static array $sentences = [];
    public static array $paragraphs = [];
    public static array $words = [];
    public static array $names = [];
    public static array $emails = [];
    public static array $imageUrls = [];
    public static array $reactions = [];
    public static array $shortMessages = [];

    public static function randomSentence(): string
    {
        return self::$sentences[array_rand(self::$sentences)] ?? fake()->sentence();
    }

    public static function randomParagraph(): string
    {
        return self::$paragraphs[array_rand(self::$paragraphs)] ?? fake()->paragraph();
    }

    public static function randomImageUrl(): string
    {
        return self::$imageUrls[array_rand(self::$imageUrls)] ?? fake()->imageUrl();
    }

    public static function randomReaction(): string
    {
        return self::$reactions[array_rand(self::$reactions)];
    }

    public static function randomShortMessage(): string
    {
        return self::$shortMessages[array_rand(self::$shortMessages)];
    }
}

