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
        Schema::create('mentorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mentee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('skill_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['one_time', 'ongoing', 'course']);
            $table->enum('format', ['individual', 'group']);
            $table->integer('max_participants')->default(1);
            $table->integer('current_participants')->default(0);
            $table->decimal('price_per_hour', 8, 2)->nullable();
            $table->decimal('total_price', 8, 2)->nullable(); // for fixed-price courses
            $table->integer('estimated_duration_hours')->nullable();
            $table->integer('total_sessions')->nullable();
            $table->integer('completed_sessions')->default(0);
            $table->json('schedule')->nullable(); // recurring schedule
            $table->enum('status', ['draft', 'published', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->json('requirements')->nullable(); // prerequisites
            $table->json('materials_included')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->decimal('rating', 2, 1)->default(0.0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_bookings')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentorships');
    }
};
