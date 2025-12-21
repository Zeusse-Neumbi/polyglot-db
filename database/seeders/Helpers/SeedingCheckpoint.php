<?php

namespace Database\Seeders\Helpers;

/**
 * Checkpoint system for resumable seeding.
 * Useful for large scales (XL/MAX) that take hours.
 */
class SeedingCheckpoint
{
    private const CHECKPOINT_FILE = 'storage/seeding_checkpoint.json';

    /**
     * Save current seeding progress.
     */
    public static function save(string $seeder, int $offset, array $metadata = []): void
    {
        $data = [
            'seeder' => $seeder,
            'offset' => $offset,
            'timestamp' => now()->toDateTimeString(),
            'metadata' => $metadata,
        ];

        file_put_contents(base_path(self::CHECKPOINT_FILE), json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Load checkpoint data if exists.
     */
    public static function load(): ?array
    {
        $path = base_path(self::CHECKPOINT_FILE);

        if (!file_exists($path)) {
            return null;
        }

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Check if we should resume from a checkpoint.
     */
    public static function shouldResume(string $seeder): bool
    {
        $data = self::load();
        return $data && $data['seeder'] === $seeder;
    }

    /**
     * Get the offset to resume from.
     */
    public static function getOffset(string $seeder): int
    {
        $data = self::load();
        if ($data && $data['seeder'] === $seeder) {
            return $data['offset'];
        }
        return 0;
    }

    /**
     * Clear checkpoint after successful completion.
     */
    public static function clear(): void
    {
        $path = base_path(self::CHECKPOINT_FILE);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Check if a checkpoint exists.
     */
    public static function exists(): bool
    {
        return file_exists(base_path(self::CHECKPOINT_FILE));
    }

    /**
     * Get info about existing checkpoint for display.
     */
    public static function getInfo(): ?string
    {
        $data = self::load();
        if (!$data) {
            return null;
        }

        return sprintf(
            "Found checkpoint: %s at offset %d (saved at %s)",
            $data['seeder'],
            $data['offset'],
            $data['timestamp']
        );
    }
}
