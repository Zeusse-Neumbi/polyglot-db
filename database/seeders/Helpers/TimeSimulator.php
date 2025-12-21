<?php

namespace Database\Seeders\Helpers;

use Carbon\Carbon;

class TimeSimulator
{
    private Carbon $startDate;
    private Carbon $endDate;
    private int $totalDurationDays;

    public function __construct()
    {
        // 5 years of history ending today
        $this->endDate = now();
        $this->startDate = now()->subYears(5);
        $this->totalDurationDays = $this->startDate->diffInDays($this->endDate);
    }

    /**
     * Calculate a realistic join date for a user based on their ID/sequence.
     * Simulates organic growth (slow start -> acceleration -> saturation).
     */
    public function getUserJoinDate(int $userIndex, int $totalUsers): Carbon
    {
        // Normalized position (0.0 to 1.0)
        $progress = $userIndex / max($totalUsers, 1);

        // Growth curve adjustment
        // Using a power function to simulate acceleration:
        // x^0.7 (concave down) pushes more users to recent times?
        // Let's use x^2 (convex) means huge growth at end.
        // Let's use x^1.5 for moderate acceleration.

        // We want distribution:
        // Y1: 5%, Y2: 15%, Y3: 25%, Y4: 30%, Y5: 25%
        // Simplified: use the raw progress but add randomness

        // Let's keep it simple: Map index directly to time, but non-linearly
        // $timeOffset = $totalDuration * ($progress ^ (1/acceleration));

        // If we want more users recently, we map low index to old times (start)
        // and high index to recent times.

        $daysToAdd = $this->totalDurationDays * pow($progress, 0.8); // 0.8 makes early growth slightly faster than linear

        // Add random variance +/- 15 days, clamped
        $daysToAdd += rand(-15, 15);
        $daysToAdd = max(0, min($this->totalDurationDays, $daysToAdd));

        return $this->startDate->copy()->addDays((int) $daysToAdd);
    }

    /**
     * Generate a timestamp for an activity (post, comment) relative to the actor's join date.
     */
    public function getActivityTimestamp(Carbon $actorJoinedAt, float $recencyBias = 0.7): Carbon
    {
        $now = now();
        if ($actorJoinedAt->gte($now)) {
            return $now;
        }

        $daysSinceJoin = $actorJoinedAt->diffInDays($now);

        // Random day in their history, biased towards recent
        // $recencyBias 0.0 = uniform, 1.0 = highly recent

        $randomFactor = mt_rand() / mt_getrandmax(); // 0.0 to 1.0

        // Apply bias: timestamp = join + duration * (rand ^ (1-bias))
        // If bias is high, (1-bias) is small, rand^small is close to 1 (end of duration)

        $power = max(0.1, 1 - $recencyBias); // e.g. 0.3
        $fraction = pow($randomFactor, $power);

        // Calculate seconds offset instead of days for full precision
        $totalSeconds = $daysSinceJoin * 24 * 60 * 60;
        $secondsOffset = $totalSeconds * $fraction;

        return $actorJoinedAt->copy()->addSeconds((int) $secondsOffset);
    }

    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }
}
