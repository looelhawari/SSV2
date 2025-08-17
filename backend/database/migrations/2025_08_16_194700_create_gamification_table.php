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
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('achievement_type'); // 'badge', 'certificate', 'milestone'
            $table->string('achievement_name');
            $table->text('description');
            $table->string('icon')->nullable();
            $table->integer('points_awarded');
            $table->json('metadata')->nullable(); // additional achievement data
            $table->timestamp('earned_at');
            $table->timestamps();
        });

        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('current_level');
            $table->integer('total_xp');
            $table->integer('xp_to_next_level');
            $table->json('skill_xp')->nullable(); // XP breakdown by skill
            $table->timestamps();
        });

        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'skill_swap_completed', 'resource_uploaded', etc.
            $table->integer('xp_amount');
            $table->string('description');
            $table->json('metadata')->nullable(); // context about the action
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gamification');
    }
};
