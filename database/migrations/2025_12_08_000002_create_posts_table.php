<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); 
            $table->text('content')->nullable();
            $table->string('media_url', 512)->nullable();
            $table->enum('media_type', ['image', 'video', 'audio', 'link', 'none'])->default('none');
            $table->enum('visibility', ['public', 'friends', 'private'])->default('public');
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at'], 'idx_post_user_created');
            $table->index('created_at', 'idx_post_created');
            $table->index(['content' => 'text'], 'ft_posts_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('posts');
    }
};
