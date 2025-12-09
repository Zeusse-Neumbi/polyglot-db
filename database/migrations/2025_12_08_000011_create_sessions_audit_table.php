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
        Schema::connection('mongodb')->create('sessions_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_token')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('device')->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->dateTime('login_at')->useCurrent();
            $table->dateTime('logout_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('sessions_audit');
    }
};
