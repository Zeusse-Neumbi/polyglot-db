<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    /**
     * Seed conversations and messages.
     */
    public function run(): void
    {
        $config = app(\Database\Seeders\Helpers\SeedingConfig::class);
        $timeSim = app(\Database\Seeders\Helpers\TimeSimulator::class);

        // Scale factors
        $totalUsers = User::count();
        // Use centralized config for conversations per user
        $targetConversations = (int) ($totalUsers * $config->getConversationsPerUser());

        // Chunking
        $chunkSize = 1000;
        $this->command->info("Creating Conversations (~{$targetConversations})...");

        $bar = $this->command->getOutput()->createProgressBar($targetConversations);
        $bar->start();

        $created = 0;
        while ($created < $targetConversations) {
            $batch = min($chunkSize, $targetConversations - $created);

            // 1. Create Conversations One by One (to get ID) 
            // but generate logic in loop

            for ($i = 0; $i < $batch; $i++) {
                $check = rand(0, 10);
                $type = ($check > 8) ? 'group' : 'private';

                $conv = Conversation::create([
                    'type' => $type,
                    'created_at' => $timeSim->getActivityTimestamp($timeSim->getStartDate(), 0.5),
                ]);

                // Participants - enforce correct counts
                $participants = [];
                if ($conv->type === 'private') {
                    // Private: EXACTLY 2 users
                    $u1 = rand(1, $totalUsers);
                    $u2 = rand(1, $totalUsers);
                    while ($u2 === $u1)
                        $u2 = rand(1, $totalUsers);
                    $participants = [$u1, $u2];
                } else {
                    // Group: at least 3 users
                    $count = rand(3, 15);
                    while (count($participants) < $count) {
                        $uid = rand(1, $totalUsers);
                        if (!in_array($uid, $participants)) {
                            $participants[] = $uid;
                        }
                    }
                }

                // Build pivot data with correct roles
                $pivotData = [];
                $participantCount = count($participants);

                foreach ($participants as $index => $uid) {
                    if ($conv->type === 'private') {
                        // Private: all members
                        $role = 'member';
                    } else {
                        // Group: first user is admin, rest are members
                        // If 10+ users, add some moderators
                        if ($index === 0) {
                            $role = 'admin';
                        } elseif ($participantCount >= 10 && $index <= 2) {
                            $role = 'moderator';
                        } else {
                            $role = 'member';
                        }
                    }

                    $pivotData[] = [
                        'conversation_id' => $conv->id,
                        'user_id' => $uid,
                        'joined_at' => $conv->created_at->toDateTimeString(),
                        'role' => $role,
                    ];
                }
                \Illuminate\Support\Facades\DB::table('conversation_user')->insert($pivotData);

                // Messages - use centralized config
                $msgRange = $config->getMessagesPerConversation();
                $msgCount = rand($msgRange[0], $msgRange[1]);
                $attachmentProb = $config->getAttachmentProbability();

                $messagesData = [];
                for ($m = 0; $m < $msgCount; $m++) {
                    $senderId = $participants[array_rand($participants)];
                    $sentAt = $timeSim->getActivityTimestamp($conv->created_at, 0.8);

                    // Use FakerPool for faster image URL generation
                    $hasAttachment = rand(1, 100) <= $attachmentProb;
                    $attachments = $hasAttachment ? json_encode([
                        ['type' => ['image', 'video', 'file'][rand(0, 2)], 'url' => FakerPool::randomImageUrl()]
                    ]) : null;

                    $messagesData[] = [
                        'conversation_id' => $conv->id,
                        'sender_id' => $senderId,
                        'body' => \Database\Seeders\Helpers\SeedingConfig::generateMessageContent(),
                        'attachments' => $attachments,
                        'is_read' => rand(0, 1),
                        'created_at' => $sentAt->toDateTimeString(),
                    ];
                }

                // WRAP IN TRANSACTION for faster writes
                if (!empty($messagesData)) {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($messagesData) {
                        Message::insert($messagesData);
                    });
                }

                $bar->advance();
            }

            $created += $batch;

            // Memory management
            gc_collect_cycles();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Conversations and messages seeded.');
    }
}
