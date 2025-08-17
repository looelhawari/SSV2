<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillSwap;

class ProfileController extends Controller
{
    /**
     * Get profile statistics
     */
    public function getStats()
    {
        try {
            $user = Auth::user();
            
            // Get skills count
            $skillsCount = UserSkill::where('user_id', $user->id)->count();
            
            // Get skill swaps count
            $swapsCount = SkillSwap::where(function($query) use ($user) {
                $query->where('requester_id', $user->id)
                      ->orWhere('provider_id', $user->id);
            })->count();
            
            // Get active swaps count
            $activeSwapsCount = SkillSwap::where('status', 'accepted')
                ->where(function($query) use ($user) {
                    $query->where('requester_id', $user->id)
                          ->orWhere('provider_id', $user->id);
                })
                ->count();
            
            // Get completed swaps count
            $completedSwapsCount = SkillSwap::where('status', 'completed')
                ->where(function($query) use ($user) {
                    $query->where('requester_id', $user->id)
                          ->orWhere('provider_id', $user->id);
                })
                ->count();
            
            // Get connections count (unique users who have swapped with)
            $connectionsCount = DB::table('skill_swaps')
                ->where('status', 'completed')
                ->where(function($query) use ($user) {
                    $query->where('requester_id', $user->id)
                          ->orWhere('provider_id', $user->id);
                })
                ->selectRaw('
                    CASE 
                        WHEN requester_id = ? THEN provider_id 
                        ELSE requester_id 
                    END as connection_id
                ', [$user->id])
                ->distinct()
                ->count();
            
            // Calculate achievements (basic achievements based on activity)
            $achievementsCount = 0;
            if ($skillsCount >= 5) $achievementsCount++;
            if ($completedSwapsCount >= 1) $achievementsCount++;
            if ($completedSwapsCount >= 5) $achievementsCount++;
            if ($completedSwapsCount >= 10) $achievementsCount++;
            if ($connectionsCount >= 5) $achievementsCount++;
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'skills_count' => $skillsCount,
                    'swaps_count' => $activeSwapsCount,
                    'total_swaps_count' => $swapsCount,
                    'completed_swaps_count' => $completedSwapsCount,
                    'connections_count' => $connectionsCount,
                    'achievements_count' => $achievementsCount,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch profile stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get profile activity history
     */
    public function getActivity()
    {
        try {
            $user = Auth::user();
            $activities = [];
            
            // Get recent skill additions
            $recentSkills = UserSkill::where('user_id', $user->id)
                ->with('skill')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
                
            foreach ($recentSkills as $userSkill) {
                $activities[] = [
                    'id' => 'skill_' . $userSkill->id,
                    'type' => 'skill_added',
                    'title' => 'Added New Skill',
                    'description' => "Added {$userSkill->skill->name} as a " . ($userSkill->skill_type === 'teaching' ? 'teaching' : 'learning') . " skill",
                    'timestamp' => $userSkill->created_at->toISOString(),
                    'icon' => '🎯'
                ];
            }
            
            // Get recent skill swaps
            $recentSwaps = SkillSwap::where('requester_id', $user->id)
                ->orWhere('provider_id', $user->id)
                ->with(['requesterSkill.skill', 'providerSkill.skill'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
                
            foreach ($recentSwaps as $swap) {
                $isRequester = $swap->requester_id === $user->id;
                $activities[] = [
                    'id' => 'swap_' . $swap->id,
                    'type' => 'swap_' . $swap->status,
                    'title' => $isRequester ? 'Applied for Skill Swap' : 'Received Skill Swap Application',
                    'description' => "Skill swap: {$swap->requesterSkill->skill->name} ↔ {$swap->providerSkill->skill->name}",
                    'timestamp' => $swap->created_at->toISOString(),
                    'icon' => $swap->status === 'completed' ? '✅' : ($swap->status === 'accepted' ? '🔄' : '📝')
                ];
            }
            
            // Sort activities by timestamp
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            // Take only the most recent 10 activities
            $activities = array_slice($activities, 0, 10);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'activities' => $activities
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch profile activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Upload profile avatar
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB max
            ]);
            
            $user = User::find(Auth::id());
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            // Store new avatar
            $file = $request->file('avatar');
            $filename = 'avatars/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('', $filename, 'public');
            
            // Update user avatar path
            $user->avatar = $path;
            $user->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar_url' => Storage::url($path),
                    'user' => $user->fresh()
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete profile avatar
     */
    public function deleteAvatar()
    {
        try {
            $user = User::find(Auth::id());
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
                $user->avatar = null;
                $user->save();
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Avatar deleted successfully',
                'data' => [
                    'user' => $user->fresh()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user profile for viewing by others
     */
    public function viewProfile($userId)
    {
        try {
            $user = User::with(['university', 'faculty', 'major'])
                ->findOrFail($userId);
            
            // Get user skills
            $skills = UserSkill::where('user_id', $userId)
                ->with('skill')
                ->get()
                ->groupBy('skill_type');
            
            // Get public profile data based on privacy settings
            $profileData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'user_type' => $user->user_type,
                'level' => $user->level,
                'xp' => $user->xp,
                'bio_en' => $user->bio_en,
                'bio_ar' => $user->bio_ar,
                'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
                'created_at' => $user->created_at,
            ];
            
            // Add fields based on privacy settings
            $privacySettings = $user->privacy_settings ?? [
                'show_email' => false,
                'show_phone' => false,
                'show_location' => true,
                'show_university' => true,
            ];
            
            if ($privacySettings['show_email'] ?? false) {
                $profileData['email'] = $user->email;
            }
            
            if ($privacySettings['show_phone'] ?? false) {
                $profileData['phone'] = $user->phone;
            }
            
            if ($privacySettings['show_location'] ?? true) {
                $profileData['location'] = $user->location;
            }
            
            if ($privacySettings['show_university'] ?? true) {
                $profileData['university'] = $user->university;
                $profileData['faculty'] = $user->faculty;
                $profileData['major'] = $user->major;
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $profileData,
                    'skills' => [
                        'teaching' => $skills->get('teaching', []),
                        'learning' => $skills->get('learning', [])
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user profile',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
