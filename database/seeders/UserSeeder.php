<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Helpers\SeedingConfig;
use Database\Seeders\Helpers\TimeSimulator;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table.
     */
    public function run(): void
    {
        $config = app(SeedingConfig::class);
        $timeSim = app(TimeSimulator::class);

        $totalUsers = $config->getUserCount();
        $chunkSize = $config->getChunkSize();

        $this->command->info("Creating {$totalUsers} Users...");
        $bar = $this->command->getOutput()->createProgressBar($totalUsers);
        $bar->start();

        // 1. Create Admin (always present)
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => $timeSim->getStartDate(),
            'updated_at' => $timeSim->getStartDate(),
        ]);
        $bar->advance();

        // 2. Batch create regular users
        // We do this in loops to manage memory
        $created = 1; // start after admin
        $passwordHash = bcrypt('password'); // Calculate once for performance

        while ($created < $totalUsers) {
            $batchSize = min($chunkSize, $totalUsers - $created);

            $usersData = [];
            for ($i = 0; $i < $batchSize; $i++) {
                $currentIndex = $created + $i;
                $joinDate = $timeSim->getUserJoinDate($currentIndex, $totalUsers);

                // We generate raw attributes to avoid model overhead during instantiation
                // converting factory to raw array
                $attributes = User::factory()->raw();

                // Override with calculated data
                $attributes['password'] = $passwordHash;
                $attributes['created_at'] = $joinDate->toDateTimeString();
                $attributes['updated_at'] = $joinDate->toDateTimeString();

                // Realism Fix: Ensure user was at least 13 when they joined
                // Date of birth = Join Date - (13 to 60 years)
                $ageAtJoin = rand(13, 60);
                $attributes['date_naissance'] = $joinDate->copy()->subYears($ageAtJoin)->subDays(rand(0, 365))->format('Y-m-d');

                $attributes['is_active'] = rand(0, 10) > 1; // 10% inactive

                // Simulate some old accounts being deleted/inactive?
                if (! $attributes['is_active']) {
                    $attributes['deleted_at'] = rand(0, 10) > 8 ? $joinDate->copy()->addMonths(rand(1, 24))->toDateTimeString() : null;
                } else {
                    $attributes['deleted_at'] = null;
                }

                $usersData[] = $attributes;
            }

            // Bulk insert is much faster
            User::insert($usersData);

            $created += $batchSize;
            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("Created {$totalUsers} users.");
    }
}
