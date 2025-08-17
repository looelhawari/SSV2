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
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->onDelete('cascade');
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('code'); // e.g., 'CS', 'EE'
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->integer('duration_years')->default(4);
            $table->decimal('min_gpa', 3, 2)->nullable();
            $table->json('required_skills')->nullable(); // skills needed for this major
            $table->json('core_subjects')->nullable(); // main subjects in this major
            $table->boolean('is_active')->default(true);
            $table->integer('student_count')->default(0);
            $table->timestamps();
            
            $table->unique(['faculty_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('majors');
    }
};
