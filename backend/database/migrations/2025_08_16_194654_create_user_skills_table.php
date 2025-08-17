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
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['owned', 'wanted']); // skills user has vs wants to learn
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->text('description')->nullable(); // how they acquired this skill or why they want it
            $table->json('certificates')->nullable(); // related certificates
            $table->boolean('is_willing_to_teach')->default(false);
            $table->boolean('is_featured')->default(false); // highlight on profile
            $table->integer('endorsements_count')->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'skill_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_skills');
    }
};
