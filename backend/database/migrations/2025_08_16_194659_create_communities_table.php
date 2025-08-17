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
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->enum('type', ['university', 'faculty', 'major', 'skill', 'interest']);
            $table->foreignId('university_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('faculty_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('major_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('cover_image')->nullable();
            $table->string('avatar')->nullable();
            $table->json('rules')->nullable(); // community guidelines
            $table->json('tags')->nullable();
            $table->boolean('is_private')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->integer('member_count')->default(0);
            $table->integer('post_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
