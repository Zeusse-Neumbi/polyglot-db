<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // TABLE friends
        Schema::create('friends', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id_1');
            $table->unsignedBigInteger('user_id_2');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'blocked'])->default('pending');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('accepted_at')->nullable();

            $table->primary(['user_id_1', 'user_id_2']);

            $table->foreign('user_id_1')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_id_2')->references('id')->on('users')->onDelete('cascade');

            $table->index('user_id_1', 'idx_friends_user_1');
            $table->index('user_id_2', 'idx_friends_user_2');
        });
        DB::statement('ALTER TABLE friends ADD CONSTRAINT check_users_diff CHECK (user_id_1 <> user_id_2)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
