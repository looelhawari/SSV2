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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('profile_picture');
            $table->text('bio_en')->nullable()->after('avatar');
            $table->text('bio_ar')->nullable()->after('bio_en');
            $table->string('location')->nullable()->after('bio_ar');
            $table->string('timezone')->default('Africa/Cairo')->after('location');
            $table->enum('preferred_contact_method', ['email', 'phone', 'platform'])->default('email')->after('timezone');
            $table->string('website')->nullable()->after('preferred_contact_method');
            $table->string('linkedin')->nullable()->after('website');
            $table->string('github')->nullable()->after('linkedin');
            $table->string('twitter')->nullable()->after('github');
            $table->integer('years_of_experience')->default(0)->after('twitter');
            $table->enum('education_level', ['high_school', 'undergraduate', 'graduate', 'postgraduate', 'phd'])->default('undergraduate')->after('years_of_experience');
            $table->integer('graduation_year')->nullable()->after('education_level');
            $table->json('privacy_settings')->nullable()->after('graduation_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'bio_en',
                'bio_ar', 
                'location',
                'timezone',
                'preferred_contact_method',
                'website',
                'linkedin',
                'github',
                'twitter',
                'years_of_experience',
                'education_level',
                'graduation_year',
                'privacy_settings'
            ]);
        });
    }
};
