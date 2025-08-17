<?php
// Add all migration records to mark them as completed

$migrations = [
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
    '2025_08_16_194546_create_universities_table',
    '2025_08_16_194618_create_faculties_table',
    '2025_08_16_194625_create_majors_table',
    '2025_08_16_194653_create_skills_table',
    '2025_08_16_194654_create_user_skills_table',
    '2025_08_16_194655_create_user_profiles_table',
    '2025_08_16_194656_create_mentorships_table',
    '2025_08_16_194656_create_skill_swaps_table',
    '2025_08_16_194658_create_resources_table',
    '2025_08_16_194659_create_communities_table',
    '2025_08_16_194700_create_gamification_table',
    '2025_08_16_194948_create_mentorship_sessions_table',
    '2025_08_16_194949_create_messages_table',
    '2025_08_16_194950_create_forum_posts_table',
    '2025_08_16_194950_create_forums_table',
    '2025_08_16_194951_create_badges_table',
    '2025_08_16_194952_create_user_badges_table',
    '2025_08_16_194953_create_certificates_table',
    '2025_08_16_194953_create_notifications_table',
    '2025_08_16_195647_create_posts_table',
    '2025_08_16_195648_create_comments_table',
    '2025_08_16_195650_create_reviews_table',
    '2025_08_16_200153_create_personal_access_tokens_table',
    '2025_08_16_200201_create_permission_tables',
    '2025_08_17_064126_add_profile_fields_to_users_table',
    '2025_08_17_071002_create_sessions_table',
    '2025_08_17_073738_create_chats_table',
    '2025_08_17_073746_create_messages_table',
    '2025_08_17_073755_create_chat_user_table',
    '2025_08_17_074226_modify_messages_table_for_chat',
    '2025_08_17_075831_create_friendships_table'
];

$batch = 1;
foreach ($migrations as $migration) {
    DB::table('migrations')->insert([
        'migration' => $migration,
        'batch' => $batch
    ]);
    echo "Added: $migration\n";
}

echo "All migrations marked as completed!\n";
