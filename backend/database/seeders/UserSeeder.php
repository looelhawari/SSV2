<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\University;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\Skill;
use App\Models\UserSkill;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some data for relationships
        $universities = University::with(['faculties.majors'])->get();
        $skills = Skill::all();
        $roles = Role::all();

        if ($universities->isEmpty()) {
            $this->command->warn('Please run UniversitySeeder first to create universities, faculties, and majors.');
            return;
        }

        if ($skills->isEmpty()) {
            $this->command->warn('Please run SkillSeeder first to create skills.');
            return;
        }

        // Create admin user
        $firstUniversity = $universities->first();
        $firstFaculty = $firstUniversity->faculties->first();
        $firstMajor = $firstFaculty->majors->first();
        
        $admin = User::create([
            'first_name' => 'SkillSwap',
            'last_name' => 'Admin',
            'email' => 'admin@skillswap.eg',
            'password' => Hash::make('admin123456'),
            'email_verified_at' => now(),
            'user_type' => 'admin',
            'university_id' => $firstUniversity->id,
            'faculty_id' => $firstFaculty->id,
            'major_id' => $firstMajor->id,
            'year_of_study' => 4,
            'is_active' => true,
            'is_verified' => true,
            'xp' => 1000,
            'level' => 10,
            'preferred_language' => 'english',
        ]);
        $admin->assignRole('admin');

        UserProfile::create([
            'user_id' => $admin->id,
            'bio_en' => 'Administrator of the SkillSwap platform',
            'bio_ar' => 'مدير منصة تبادل المهارات',
            'interests' => json_encode(['education', 'technology', 'platform_management']),
            'career_goals' => json_encode(['improve_education', 'skill_sharing', 'community_building']),
            'social_links' => json_encode([
                'website' => 'https://skillswap.eg',
                'linkedin' => 'https://linkedin.com/company/skillswap'
            ]),
            'is_mentor' => true,
        ]);

        // Create sample students
        $students = [
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Hassan',
                'email' => 'ahmed.hassan@skillswap.eg',
                'user_type' => 'student',
                'bio' => 'Computer Science student passionate about web development and AI',
                'year_of_study' => 3,
            ],
            [
                'first_name' => 'Fatma',
                'last_name' => 'Mohamed',
                'email' => 'fatma.mohamed@skillswap.eg',
                'user_type' => 'student',
                'bio' => 'Business Administration student interested in digital marketing',
                'year_of_study' => 4,
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Ali',
                'email' => 'omar.ali@skillswap.eg',
                'user_type' => 'student',
                'bio' => 'Engineering student specializing in mechanical design',
                'year_of_study' => 2,
            ],
            [
                'first_name' => 'Nora',
                'last_name' => 'Mahmoud',
                'email' => 'nora.mahmoud@skillswap.eg',
                'user_type' => 'student',
                'bio' => 'Medical student interested in clinical research',
                'year_of_study' => 1,
            ],
            [
                'first_name' => 'Youssef',
                'last_name' => 'Ibrahim',
                'email' => 'youssef.ibrahim@skillswap.eg',
                'user_type' => 'student',
                'bio' => 'Design student focusing on UI/UX and graphic design',
                'year_of_study' => 3,
            ],
        ];

        // Create sample mentors
        $mentors = [
            [
                'first_name' => 'Maha',
                'last_name' => 'Abdel Rahman',
                'email' => 'maha.rahman@skillswap.eg',
                'user_type' => 'mentor',
                'bio' => 'Senior software engineer with 8+ years experience in full-stack development',
                'year_of_study' => 4,
                'linkedin_url' => 'https://linkedin.com/in/maha-rahman',
            ],
            [
                'first_name' => 'Khaled',
                'last_name' => 'Farouk',
                'email' => 'khaled.farouk@skillswap.eg',
                'user_type' => 'mentor',
                'bio' => 'Data scientist and machine learning expert at a leading tech company',
                'year_of_study' => 4,
                'github_url' => 'https://github.com/khaled-farouk',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Mansour',
                'email' => 'sarah.mansour@skillswap.eg',
                'user_type' => 'mentor',
                'bio' => 'UX designer and creative director with expertise in user research',
                'year_of_study' => 4,
                'website' => 'https://sarahmansour.design',
            ],
        ];

        foreach ($students as $studentData) {
            $university = $universities->random();
            $faculty = $university->faculties->random();
            $major = $faculty->majors->random();

            $user = User::create([
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'email' => $studentData['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'user_type' => $studentData['user_type'],
                'university_id' => $university->id,
                'faculty_id' => $faculty->id,
                'major_id' => $major->id,
                'year_of_study' => $studentData['year_of_study'],
                'is_active' => true,
                'is_verified' => true,
                'xp' => rand(50, 500),
                'level' => rand(1, 5),
                'preferred_language' => 'arabic',
            ]);

            $user->assignRole('student');

            UserProfile::create([
                'user_id' => $user->id,
                'bio_en' => $studentData['bio'],
                'bio_ar' => null,
                'interests' => json_encode(['learning', 'technology', 'career_development']),
                'career_goals' => json_encode(['skill_improvement', 'networking', 'academic_success']),
                'social_links' => json_encode([
                    'linkedin' => $studentData['linkedin_url'] ?? null,
                    'github' => $studentData['github_url'] ?? null,
                    'website' => $studentData['website'] ?? null,
                ]),
                'availability_status' => 'available',
                'is_mentor' => false,
            ]);

            // Add random skills for students
            $randomSkills = $skills->random(rand(2, 5));
            foreach ($randomSkills as $skill) {
                UserSkill::create([
                    'user_id' => $user->id,
                    'skill_id' => $skill->id,
                    'type' => rand(0, 1) ? 'owned' : 'wanted',
                    'proficiency_level' => ['beginner', 'intermediate', 'advanced'][rand(0, 2)],
                    'is_willing_to_teach' => rand(0, 1),
                    'years_of_experience' => rand(0, 3),
                ]);
            }
        }

        foreach ($mentors as $mentorData) {
            $university = $universities->random();
            $faculty = $university->faculties->random();
            $major = $faculty->majors->random();

            $user = User::create([
                'first_name' => $mentorData['first_name'],
                'last_name' => $mentorData['last_name'],
                'email' => $mentorData['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'user_type' => $mentorData['user_type'],
                'university_id' => $university->id,
                'faculty_id' => $faculty->id,
                'major_id' => $major->id,
                'year_of_study' => $mentorData['year_of_study'],
                'is_active' => true,
                'is_verified' => true,
                'xp' => rand(800, 2000),
                'level' => rand(8, 15),
                'preferred_language' => 'english',
            ]);

            $user->assignRole('mentor');

            UserProfile::create([
                'user_id' => $user->id,
                'bio_en' => $mentorData['bio'],
                'bio_ar' => null,
                'interests' => json_encode(['mentoring', 'teaching', 'technology', 'career_development']),
                'career_goals' => json_encode(['help_students', 'share_knowledge', 'build_community']),
                'social_links' => json_encode([
                    'linkedin' => $mentorData['linkedin_url'] ?? null,
                    'github' => $mentorData['github_url'] ?? null,
                    'website' => $mentorData['website'] ?? null,
                ]),
                'availability_status' => 'available',
                'is_mentor' => true,
                'rating' => number_format(rand(40, 50) / 10, 1),
                'total_reviews' => rand(5, 25),
                'total_sessions_given' => rand(10, 100),
            ]);

            // Add skills for mentors (more and with mentor capability)
            $randomSkills = $skills->random(rand(4, 8));
            foreach ($randomSkills as $skill) {
                UserSkill::create([
                    'user_id' => $user->id,
                    'skill_id' => $skill->id,
                    'type' => 'owned',
                    'proficiency_level' => ['intermediate', 'advanced'][rand(0, 1)],
                    'is_willing_to_teach' => true,
                    'years_of_experience' => rand(2, 10),
                ]);
            }
        }

        $this->command->info('Sample users created successfully!');
        $this->command->info('Admin: admin@skillswap.eg / admin123456');
        $this->command->info('Students: ahmed.hassan@skillswap.eg / password123 (and others)');
        $this->command->info('Mentors: maha.rahman@skillswap.eg / password123 (and others)');
    }
}
