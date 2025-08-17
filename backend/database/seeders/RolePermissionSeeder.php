<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Skill permissions
            'view skills',
            'create skills',
            'edit skills',
            'delete skills',
            'verify skills',
            
            // Skill swap permissions
            'view skill swaps',
            'create skill swaps',
            'edit skill swaps',
            'delete skill swaps',
            
            // Mentorship permissions
            'view mentorships',
            'create mentorships',
            'edit mentorships',
            'delete mentorships',
            
            // Resource permissions
            'view resources',
            'create resources',
            'edit resources',
            'delete resources',
            'verify resources',
            
            // University management
            'manage universities',
            'manage faculties',
            'manage majors',
            
            // Content moderation
            'moderate content',
            'verify content',
            'ban users',
            
            // Analytics
            'view analytics',
            'export data',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin role - all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Moderator role - content moderation permissions
        $moderatorRole = Role::create(['name' => 'moderator']);
        $moderatorRole->givePermissionTo([
            'view users',
            'view skills',
            'view skill swaps',
            'view mentorships',
            'view resources',
            'moderate content',
            'verify content',
            'verify skills',
            'verify resources',
        ]);

        // Mentor role - teaching related permissions
        $mentorRole = Role::create(['name' => 'mentor']);
        $mentorRole->givePermissionTo([
            'view users',
            'view skills',
            'create skills',
            'edit skills',
            'view skill swaps',
            'create skill swaps',
            'edit skill swaps',
            'view mentorships',
            'create mentorships',
            'edit mentorships',
            'view resources',
            'create resources',
            'edit resources',
        ]);

        // Student role - basic permissions
        $studentRole = Role::create(['name' => 'student']);
        $studentRole->givePermissionTo([
            'view users',
            'view skills',
            'create skills',
            'view skill swaps',
            'create skill swaps',
            'edit skill swaps',
            'view mentorships',
            'view resources',
            'create resources',
        ]);
    }
}
