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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio_en')->nullable();
            $table->text('bio_ar')->nullable();
            $table->json('interests')->nullable(); // academic and personal interests
            $table->json('hobbies')->nullable();
            $table->json('career_goals')->nullable();
            $table->json('achievements')->nullable(); // academic achievements
            $table->json('extracurricular_activities')->nullable();
            $table->json('work_experience')->nullable();
            $table->json('projects')->nullable(); // portfolio projects
            $table->json('social_links')->nullable(); // GitHub, LinkedIn, etc.
            $table->string('cv_file')->nullable();
            $table->enum('availability_status', ['available', 'busy', 'unavailable'])->default('available');
            $table->json('available_times')->nullable(); // when they're available for skill swaps
            $table->decimal('rating', 2, 1)->default(0.0); // mentor rating
            $table->integer('total_reviews')->default(0);
            $table->boolean('is_mentor')->default(false);
            $table->decimal('hourly_rate', 8, 2)->nullable(); // for paid mentorship
            $table->json('mentor_subjects')->nullable(); // subjects they can mentor in
            $table->integer('total_sessions_given')->default(0);
            $table->integer('total_sessions_taken')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
