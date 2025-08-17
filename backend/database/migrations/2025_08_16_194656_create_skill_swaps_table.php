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
        Schema::create('skill_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('provider_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('requested_skill_id')->constrained('skills')->onDelete('cascade');
            $table->foreignId('offered_skill_id')->nullable()->constrained('skills')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('swap_type', ['skill_for_skill', 'free_teaching', 'paid_teaching']);
            $table->decimal('price', 8, 2)->nullable(); // for paid teaching
            $table->integer('estimated_hours')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('format', ['online', 'offline', 'both']);
            $table->json('preferred_times')->nullable();
            $table->json('location_preferences')->nullable(); // for offline sessions
            $table->enum('status', ['pending', 'matched', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('requester_notes')->nullable();
            $table->text('provider_notes')->nullable();
            $table->integer('requester_rating')->nullable(); // 1-5 stars
            $table->integer('provider_rating')->nullable(); // 1-5 stars
            $table->text('requester_review')->nullable();
            $table->text('provider_review')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_swaps');
    }
};
