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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['text', 'image', 'file', 'audio', 'video'])->default('text');
            $table->text('content'); // Message content or file path
            $table->string('file_name')->nullable(); // Original file name
            $table->string('file_size')->nullable(); // File size in bytes
            $table->string('mime_type')->nullable(); // File MIME type
            $table->json('metadata')->nullable(); // Additional data (dimensions, duration, etc.)
            $table->unsignedBigInteger('reply_to_message_id')->nullable(); // For replies
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->json('read_by')->nullable(); // Track who read the message
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reply_to_message_id')->references('id')->on('messages')->onDelete('set null');
            
            $table->index(['chat_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
