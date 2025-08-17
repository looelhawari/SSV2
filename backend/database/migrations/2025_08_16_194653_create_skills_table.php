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
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('slug')->unique();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('category'); // 'programming', 'design', 'language', 'academic', 'soft_skills'
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
            $table->json('prerequisites')->nullable(); // skills needed before learning this skill
            $table->json('tags')->nullable(); // related keywords
            $table->string('icon')->nullable(); // icon URL or class
            $table->integer('popularity_score')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
