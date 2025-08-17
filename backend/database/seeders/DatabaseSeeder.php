<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in order due to dependencies
        $this->call([
            RolePermissionSeeder::class,    // First: Create roles and permissions
            UniversitySeeder::class,        // Second: Create universities, faculties, majors
            SkillSeeder::class,            // Third: Create skills
            UserSeeder::class,             // Fourth: Create users with relationships
        ]);
    }
}
