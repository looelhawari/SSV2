<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\SimpleUserController;
use App\Http\Controllers\Api\UniversityController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\SkillSwapController;
use App\Http\Controllers\Api\MentorshipController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Universities, Faculties, and Majors (public for registration)
Route::get('universities', [UniversityController::class, 'index']);
Route::get('universities/{id}', [UniversityController::class, 'show']);
Route::get('universities/{id}/faculties', [UniversityController::class, 'faculties']);
Route::get('universities/{universityId}/faculties/{facultyId}/majors', [UniversityController::class, 'facultyMajors']);

// Public Skills
Route::get('skills', [SkillController::class, 'index']);
Route::get('skills/{id}', [SkillController::class, 'show']);
Route::get('skills/popular', [SkillController::class, 'getPopularSkills']);
Route::get('skills/by-category', [SkillController::class, 'getSkillsByCategory']);

// Public Skill Swaps (browse without auth)
Route::get('skill-swaps', [SkillSwapController::class, 'index']);
Route::get('skill-swaps/{id}', [SkillSwapController::class, 'show']);

// Public Resources (browse without auth)
Route::get('resources', [ResourceController::class, 'index']);
Route::get('resources/{id}', [ResourceController::class, 'show']);
Route::get('resources/popular', [ResourceController::class, 'popular']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });

    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('stats', [ProfileController::class, 'getStats']);
        Route::get('activity', [ProfileController::class, 'getActivity']);
        Route::post('avatar', [ProfileController::class, 'uploadAvatar']);
        Route::delete('avatar', [ProfileController::class, 'deleteAvatar']);
        Route::get('view/{userId}', [ProfileController::class, 'viewProfile']);
    });

    // User Management (Simple version without complex relationships)
    Route::prefix('users')->group(function () {
        Route::get('/', [SimpleUserController::class, 'search']);
        Route::post('/{id}/friend-request', [SimpleUserController::class, 'sendFriendRequest']);
        Route::post('/{id}/accept-friend', [SimpleUserController::class, 'acceptFriendRequest']);
        Route::post('/{id}/block', [SimpleUserController::class, 'blockUser']);
        Route::post('/{id}/unblock', [SimpleUserController::class, 'unblockUser']);
    });

    // Skills Management
    Route::prefix('skills')->group(function () {
        Route::post('/', [SkillController::class, 'store']);
        Route::put('/{id}', [SkillController::class, 'update']);
        Route::delete('/{id}', [SkillController::class, 'destroy']);
        
        // User Skills
        Route::post('/user-skills', [SkillController::class, 'addUserSkill']);
        Route::put('/user-skills/{id}', [SkillController::class, 'updateUserSkill']);
        Route::delete('/user-skills/{id}', [SkillController::class, 'removeUserSkill']);
        Route::get('/user-skills/{userId?}', [SkillController::class, 'getUserSkills']);
    });

    // Skill Swaps
    Route::prefix('skill-swaps')->group(function () {
        Route::post('/', [SkillSwapController::class, 'store']);
        Route::put('/{id}', [SkillSwapController::class, 'update']);
        Route::delete('/{id}', [SkillSwapController::class, 'destroy']);
        Route::post('/{id}/apply', [SkillSwapController::class, 'apply']);
        Route::post('/{id}/accept', [SkillSwapController::class, 'accept']);
        Route::post('/{id}/complete', [SkillSwapController::class, 'complete']);
        Route::post('/{id}/cancel', [SkillSwapController::class, 'cancel']);
        Route::post('/{id}/rate', [SkillSwapController::class, 'rate']);
        Route::get('/my-swaps', [SkillSwapController::class, 'getUserSkillSwaps']);
        Route::get('/recommendations', [SkillSwapController::class, 'getRecommendations']);
        Route::get('/stats', [SkillSwapController::class, 'getStats']);
    });

    // Mentorships
    Route::prefix('mentorships')->group(function () {
        Route::get('/', [MentorshipController::class, 'index']);
        Route::post('/', [MentorshipController::class, 'store']);
        Route::get('/{id}', [MentorshipController::class, 'show']);
        Route::put('/{id}', [MentorshipController::class, 'update']);
        Route::delete('/{id}', [MentorshipController::class, 'destroy']);
        Route::get('/find-mentors', [MentorshipController::class, 'findMentors']);
        Route::get('/stats', [MentorshipController::class, 'stats']);
    });

    // Resources
    Route::prefix('resources')->group(function () {
        Route::post('/', [ResourceController::class, 'store']);
        Route::put('/{id}', [ResourceController::class, 'update']);
        Route::delete('/{id}', [ResourceController::class, 'destroy']);
        Route::get('/{id}/download', [ResourceController::class, 'download']);
        Route::get('/my-resources', [ResourceController::class, 'myResources']);
        Route::get('/stats', [ResourceController::class, 'stats']);
    });

    // Chat System
    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'store']);
        Route::get('/{id}', [ChatController::class, 'show']);
        Route::put('/{id}', [ChatController::class, 'update']);
        Route::delete('/{id}', [ChatController::class, 'destroy']);
        Route::post('/{id}/mark-as-read', [ChatController::class, 'markAsRead']);
        
        // Messages within chats
        Route::prefix('{chatId}/messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'store']);
            Route::put('/{messageId}', [MessageController::class, 'update']);
            Route::delete('/{messageId}', [MessageController::class, 'destroy']);
            Route::post('/{messageId}/read', [MessageController::class, 'markAsRead']);
            Route::get('/search', [MessageController::class, 'search']);
            Route::post('/typing', [MessageController::class, 'typing']);
        });
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/read/all', [NotificationController::class, 'deleteAllRead']);
    });

    // Universities Management (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::prefix('universities')->group(function () {
            Route::post('/', [UniversityController::class, 'store']);
            Route::put('/{id}', [UniversityController::class, 'update']);
            Route::delete('/{id}', [UniversityController::class, 'destroy']);
            Route::post('/{id}/faculties', [UniversityController::class, 'storeFaculty']);
            Route::post('/{universityId}/faculties/{facultyId}/majors', [UniversityController::class, 'storeMajor']);
            Route::get('/{id}/statistics', [UniversityController::class, 'statistics']);
        });
    });
});

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'SkillSwap API is running',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
