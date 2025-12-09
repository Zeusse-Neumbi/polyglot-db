<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // TABLE groups
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
        });

        // TABLE group_user
        Schema::create('group_user', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['member', 'moderator', 'admin'])->default('member');
            $table->dateTime('joined_at')->useCurrent();

            $table->primary(['group_id', 'user_id']);
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
    }
};
