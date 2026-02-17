<?php

use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;

$user = User::first();
if (!$user) {
    echo "No user found.\n";
    exit;
}

$conversation = Conversation::first();
if (!$conversation) {
    echo "No conversation found.\n";
    exit;
}

echo "Attempting update with take()...\n";

try {
    Message::where('conversation_id', $conversation->id)
        ->take(1)
        ->update(['is_read' => true]);
    echo "Update success!\n";
} catch (\Exception $e) {
    echo "Update failed: " . $e->getMessage() . "\n";
}
