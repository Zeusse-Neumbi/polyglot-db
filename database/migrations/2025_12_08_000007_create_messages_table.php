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
        Schema::connection('mongodb')->create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('sender_id')->index();
            $table->text('body')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            // Note: MongoDB handles timestamps differently but dateTime is fine for storage.
            // Often we use $table->timestamps() for auto management.
            // But let's keep created_at explicit if intended.

            $table->index(['conversation_id', 'created_at'], 'idx_messages_conv_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('messages');
    }
};
