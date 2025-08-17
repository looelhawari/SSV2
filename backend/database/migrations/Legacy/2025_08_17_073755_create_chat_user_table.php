<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['member', 'admin', 'owner'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->json('settings')->nullable(); // User-specific chat settings
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('last_read_message_id')->references('id')->on('messages')->onDelete('set null');
            
            $table->unique(['chat_id', 'user_id']);
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_user');
    }
};
