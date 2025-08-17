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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'group'])->default('private');
            $table->string('name')->nullable(); // For group chats
            $table->text('description')->nullable(); // For group chats
            $table->string('avatar')->nullable(); // Group chat avatar
            $table->unsignedBigInteger('created_by')->nullable(); // Who created the chat
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['type', 'is_active']);
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
