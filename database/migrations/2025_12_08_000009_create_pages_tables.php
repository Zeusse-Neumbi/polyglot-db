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
        // TABLE pages
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });

        // TABLE page_followers
        Schema::create('page_followers', function (Blueprint $table) {
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('notifications_on')->default(true);
            $table->dateTime('followed_at')->useCurrent();

            $table->primary(['page_id', 'user_id']);
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_followers');
        Schema::dropIfExists('pages');
    }
};
