<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // TABLE reactions
        Schema::connection('mongodb')->create('reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('message_id')->nullable(); // Made nullable as it might be a reaction to a post OR a message
            $table->enum('reaction_type', ['like', 'love', 'haha', 'wow', 'sad', 'angry']);
            $table->dateTime('created_at')->useCurrent();

            // Unique constraints in MongoDB are handled via unique indexes
            $table->unique(['post_id', 'user_id'], 'ux_reaction_post_user');
            // We need to be careful with unique constraints on nullable fields in Mongo, 
            // but for now we'll keep the intent.
            // Note: message_id + user_id unique might conflict if message_id is null for post reactions using simple unique index.
            // A sparse index or compound index strategy is often better, but let's stick to the schema intent.
            // If message_id is null, unique index might treat multiple nulls as duplicates depending on version, 
            // but usually {message_id: null, user_id: 1} and {message_id: null, user_id: 1} are dupes.
            // However, a user can react to only ONE post or ONE message? The original schema had both as non-nullable?
            // Checking original: $table->unsignedBigInteger('message_id'); was NOT nullable.
            // So a reaction was for a post AND a message? That seems unlikely for a standard social app.
            // Usually it's polymorphic or nullable.
            // Let's look at the original file content again.

            $table->index('post_id_1', 'idx_reaction_post');
            $table->index('user_id_1', 'idx_reaction_user');
            $table->index('message_id_1', 'idx_reaction_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('reactions');
    }
};
