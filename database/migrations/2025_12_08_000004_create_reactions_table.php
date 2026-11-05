<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->unsignedBigInteger('message_id')->nullable();
            $table->enum('reaction_type', ['like', 'love', 'haha', 'wow', 'sad', 'angry']);
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['post_id', 'user_id'], 'ux_reaction_post_user');

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
