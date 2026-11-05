<?php

namespace Database\Seeders\Helpers;

class SeedingConfig
{
    // Define available scales
    const SCALE_SMALL = 'small';

    const SCALE_MEDIUM = 'medium';

    const SCALE_LARGE = 'large';

    const SCALE_XL = 'xl';

    const SCALE_MAX = 'max';

    // Scale definitions
    protected static array $scales = [
        self::SCALE_SMALL => [
            'label' => 'Small (1k users - ~3 mins)',
            'users' => 1000,
            'chunk_size' => 100,
        ],
        self::SCALE_MEDIUM => [
            'label' => 'Medium (10k users - ~30 mins)',
            'users' => 10000,
            'chunk_size' => 500,
        ],
        self::SCALE_LARGE => [
            'label' => 'Large (50k users - ~2.5 hours)',
            'users' => 50000,
            'chunk_size' => 1000,
        ],
        self::SCALE_XL => [
            'label' => 'XL (100k users - ~5 hours)',
            'users' => 100000,
            'chunk_size' => 2000,
        ],
        self::SCALE_MAX => [
            'label' => 'MAX (250k users - ~12+ hours)',
            'users' => 250000,
            'chunk_size' => 5000,
        ],
    ];

    private string $currentScale;

    private array $config;

    public function __construct(string $scale = self::SCALE_SMALL)
    {
        if (! array_key_exists($scale, self::$scales)) {
            throw new \InvalidArgumentException("Invalid scale: $scale");
        }
        $this->currentScale = $scale;
        $this->config = self::$scales[$scale];
    }

    public static function getAvailableScales(): array
    {
        return self::$scales;
    }

    public function getUserCount(): int
    {
        return $this->config['users'];
    }

    public function getGroupCount(): int
    {
        // ~10% of users create a group (Higher density for testing)
        // Small: 100. Large: 5000.
        return (int) ceil($this->config['users'] * 0.10);
    }

    public function getPageCount(): int
    {
        // ~5% of users manage a page (Brands/Communities)
        // Small: 50. Large: 2500.
        return (int) ceil($this->config['users'] * 0.05);
    }

    /**
     * Get safe chunk size for batch inserts
     */
    public function getChunkSize(): int
    {
        return $this->config['chunk_size'];
    }

    public function getScaleLabel(): string
    {
        return $this->config['label'];
    }

    // Helper multipliers for related entities
    public function getAvgFriendsPerUser(): int
    {
        // Slightly denser graph for smaller datasets
        return match ($this->currentScale) {
            self::SCALE_SMALL => 50, // Increased connectivity
            default => 40
        };
    }

    // ========== CENTRALIZED RATIOS ==========

    public function getConversationsPerUser(): int
    {
        return 5;
    }

    public function getMessagesPerConversation(): array
    {
        return [50, 150]; // [min, max]
    }

    public function getAttachmentProbability(): int
    {
        return 20; // 20% chance
    }

    public function getPostsPerUser(): array
    {
        return [
            'power_user' => [40, 80],
            'casual' => [15, 30],
            'lurker' => [1, 10],
            'inactive' => [0, 0],
        ];
    }

    public function getReactionsPerPost(): array
    {
        return [
            'viral' => [100, 300],
            'popular' => [20, 80],
            'standard' => [5, 20],
            'ignored' => [0, 0],
        ];
    }

    public function getCommentsPerPost(): array
    {
        return [
            'viral' => [30, 80],
            'popular' => [10, 30],
            'active' => [3, 10],
            'quiet' => [0, 0],
        ];
    }

    // ========== USER PERSONAS ==========

    /**
     * Assign a persistent persona to a user based on their ID.
     * This ensures consistent activity patterns across posts, comments, etc.
     */
    public static function getUserPersona(int $userId): string
    {
        // Use modulo to deterministically assign personas
        $hash = crc32("user_{$userId}");
        $rand = $hash % 100;

        if ($rand < 10) {
            return 'power_user';
        }  // 10%
        if ($rand < 50) {
            return 'casual';
        }      // 40%
        if ($rand < 80) {
            return 'lurker';
        }      // 30%

        return 'inactive';                     // 20%
    }

    /**
     * Generate varied message content based on type.
     */
    public static function generateMessageContent(): string
    {
        $type = rand(1, 100);

        if ($type <= 30) {
            // Short reactions (30%)
            return fake()->randomElement(['ok', 'lol', '👍', '😂', 'nice!', 'cool', 'thanks!', '❤️', 'wow', 'haha']);
        } elseif ($type <= 60) {
            // Single sentence (30%)
            return fake()->sentence();
        } elseif ($type <= 85) {
            // Regular message (25%)
            return fake()->sentences(rand(2, 3), true);
        } else {
            // Long message (15%)
            return fake()->paragraph();
        }
    }

    /**
     * Generate varied post content.
     */
    public static function generatePostContent(): string
    {
        $type = rand(1, 100);

        if ($type <= 20) {
            // Short status (20%)
            return fake()->sentence();
        } elseif ($type <= 40) {
            // Question (20%)
            return fake()->randomElement([
                'Anyone know '.fake()->sentence().'?',
                'What do you think about '.fake()->words(3, true).'?',
                'Has anyone tried '.fake()->words(2, true).'?',
            ]);
        } elseif ($type <= 70) {
            // Regular post (30%)
            return fake()->paragraph();
        } else {
            // Long post (30%)
            return fake()->paragraphs(rand(2, 3), true);
        }
    }
}
