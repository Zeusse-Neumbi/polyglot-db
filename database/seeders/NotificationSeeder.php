<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\SessionAudit;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Seed notifications and session audits.
     */
    public function run(): void
    {
        $config = app(\Database\Seeders\Helpers\SeedingConfig::class);
        $timeSim = app(\Database\Seeders\Helpers\TimeSimulator::class);

        $this->command->info('Creating Notifications and Session Audits...');

        $totalUsers = User::count();
        $chunkSize = $config->getChunkSize();

        $bar = $this->command->getOutput()->createProgressBar($totalUsers);
        $bar->start();

        $currentId = 1;
        while ($currentId <= $totalUsers) {
            $users = User::whereBetween('id', [$currentId, $currentId + $chunkSize - 1])
                ->get(['id', 'created_at']);

            $notifsData = [];
            $sessionsData = [];

            foreach ($users as $user) {
                // Notifications (recent ones mainly)
                // Generate 150-300 notifications per user (Approx 200k total for 1k users)
                $count = rand(150, 300);
                for ($k = 0; $k < $count; $k++) {
                    $createdAt = $timeSim->getActivityTimestamp($user->created_at, 0.9); // Mostly recent
                    $notifsData[] = [
                        'user_id' => $user->id,
                        'type' => 'App\Notifications\GenericNotification',
                        'data' => json_encode(['message' => fake()->sentence()]),
                        'is_read' => rand(0, 1),
                        'created_at' => $createdAt->toDateTimeString(),
                    ];
                }

                // Session Audits
                // 20-50 sessions
                $sCount = rand(20, 50);
                for ($s = 0; $s < $sCount; $s++) {
                    $loginAt = $timeSim->getActivityTimestamp($user->created_at, 0.9);
                    $sessionsData[] = [
                        'user_id' => $user->id,
                        'ip_address' => fake()->ipv4(),
                        'user_agent' => fake()->userAgent(),
                        'login_at' => $loginAt->toDateTimeString(),
                        'logout_at' => rand(0, 1) ? $loginAt->copy()->addMinutes(rand(5, 120))->toDateTimeString() : null,
                    ];
                }
            }

            if (!empty($notifsData)) {
                foreach (array_chunk($notifsData, 1000) as $chunk) {
                    Notification::insert($chunk);
                }
            }

            if (!empty($sessionsData)) {
                foreach (array_chunk($sessionsData, 1000) as $chunk) {
                    SessionAudit::insert($chunk);
                }
            }

            $currentId += $chunkSize;
            $bar->advance($users->count());
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Created notifications and audit logs.');
    }
}
