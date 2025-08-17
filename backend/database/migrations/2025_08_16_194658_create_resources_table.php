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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // uploader
            $table->foreignId('university_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('faculty_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('major_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['document', 'video', 'audio', 'link', 'quiz', 'assignment']);
            $table->enum('category', ['lecture_notes', 'textbook', 'assignment', 'project', 'exam', 'tutorial', 'research']);
            $table->string('subject'); // e.g., 'Mathematics', 'Programming'
            $table->integer('academic_year')->nullable();
            $table->enum('semester', ['fall', 'spring', 'summer'])->nullable();
            $table->string('course_code')->nullable();
            $table->string('instructor_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->string('external_url')->nullable(); // for links
            $table->json('tags')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('language', ['arabic', 'english', 'both']);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_downloadable')->default(true);
            $table->enum('access_level', ['public', 'university', 'faculty', 'major', 'premium']);
            $table->decimal('price', 8, 2)->nullable(); // for premium resources
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->decimal('rating', 2, 1)->default(0.0);
            $table->integer('rating_count')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
