<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\UserSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SkillController extends Controller
{
    public function index(Request $request)
    {
        $query = Skill::query()->where('is_active', true);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by difficulty level
        if ($request->has('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_en', 'LIKE', "%{$search}%")
                  ->orWhere('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('description_en', 'LIKE', "%{$search}%")
                  ->orWhere('description_ar', 'LIKE', "%{$search}%");
            });
        }

        // Order by popularity by default
        $query->orderBy('popularity_score', 'desc');

        $skills = $query->get();

        // Convert difficulty_level to number if it's stored as string
        $skills = $skills->map(function ($skill) {
            if (is_string($skill->difficulty_level)) {
                $difficultyMap = [
                    'beginner' => 1,
                    'intermediate' => 2,
                    'advanced' => 3
                ];
                $skill->difficulty_level = $difficultyMap[$skill->difficulty_level] ?? 1;
            }
            return $skill;
        });

        return response()->json([
            'status' => 'success',
            'data' => $skills
        ]);
    }

    public function show(Skill $skill)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'skill' => $skill->load('prerequisites')
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'category' => 'required|in:programming,design,language,academic,soft_skills,business,creative,technical',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:skills,id',
            'tags' => 'nullable|array',
            'icon' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $skill = Skill::create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'slug' => Str::slug($request->name_en),
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'category' => $request->category,
            'difficulty_level' => $request->difficulty_level,
            'prerequisites' => $request->prerequisites,
            'tags' => $request->tags,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Skill created successfully',
            'data' => ['skill' => $skill]
        ], 201);
    }

    public function update(Request $request, Skill $skill)
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'description_en' => 'sometimes|nullable|string',
            'description_ar' => 'sometimes|nullable|string',
            'category' => 'sometimes|in:programming,design,language,academic,soft_skills,business,creative,technical',
            'difficulty_level' => 'sometimes|in:beginner,intermediate,advanced',
            'prerequisites' => 'sometimes|nullable|array',
            'prerequisites.*' => 'exists:skills,id',
            'tags' => 'sometimes|nullable|array',
            'icon' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['name_en'])) {
            $data['slug'] = Str::slug($data['name_en']);
        }

        $skill->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Skill updated successfully',
            'data' => ['skill' => $skill->fresh()]
        ]);
    }

    public function addUserSkill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skill_id' => 'required|exists:skills,id',
            'proficiency_level' => 'required|integer|min:1|max:5',
            'is_teaching' => 'required|boolean',
            'is_learning' => 'required|boolean',
            'years_of_experience' => 'nullable|integer|min:0|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Convert proficiency level number to string
        $proficiencyMap = [
            1 => 'beginner',
            2 => 'intermediate', 
            3 => 'intermediate',
            4 => 'advanced',
            5 => 'expert'
        ];

        // Determine type based on is_teaching flag
        $type = $request->is_teaching ? 'owned' : 'wanted';

        // Check if user already has this skill
        $existingUserSkill = UserSkill::where([
            'user_id' => $request->user()->id,
            'skill_id' => $request->skill_id,
        ])->first();

        if ($existingUserSkill) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have this skill'
            ], 422);
        }

        $userSkill = UserSkill::create([
            'user_id' => $request->user()->id,
            'skill_id' => $request->skill_id,
            'type' => $type,
            'proficiency_level' => $proficiencyMap[$request->proficiency_level],
            'years_of_experience' => $request->years_of_experience ?? 0,
            'is_willing_to_teach' => $request->is_teaching,
        ]);

        // Return in the format expected by frontend
        $userSkill->load('skill');
        $formattedSkill = [
            'id' => $userSkill->id,
            'skill' => $userSkill->skill,
            'proficiency_level' => $request->proficiency_level, // Return original number
            'is_teaching' => $request->is_teaching,
            'is_learning' => $request->is_learning,
            'years_of_experience' => $userSkill->years_of_experience,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Skill added successfully',
            'data' => $formattedSkill
        ], 201);
    }

    public function updateUserSkill(Request $request, UserSkill $userSkill)
    {
        // Check if the user owns this skill
        if ($userSkill->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'proficiency_level' => 'sometimes|integer|min:1|max:5',
            'is_teaching' => 'sometimes|boolean',
            'is_learning' => 'sometimes|boolean',
            'years_of_experience' => 'sometimes|integer|min:0|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [];

        // Convert proficiency level if provided
        if ($request->has('proficiency_level')) {
            $proficiencyMap = [
                1 => 'beginner',
                2 => 'intermediate', 
                3 => 'intermediate',
                4 => 'advanced',
                5 => 'expert'
            ];
            $updateData['proficiency_level'] = $proficiencyMap[$request->proficiency_level];
        }

        // Update type and teaching willingness
        if ($request->has('is_teaching')) {
            $updateData['type'] = $request->is_teaching ? 'owned' : 'wanted';
            $updateData['is_willing_to_teach'] = $request->is_teaching;
        }

        if ($request->has('years_of_experience')) {
            $updateData['years_of_experience'] = $request->years_of_experience;
        }

        $userSkill->update($updateData);

        // Return in frontend format
        $proficiencyMap = [
            'beginner' => 1,
            'intermediate' => 3,
            'advanced' => 4,
            'expert' => 5
        ];

        $formattedSkill = [
            'id' => $userSkill->id,
            'skill' => $userSkill->skill,
            'proficiency_level' => $proficiencyMap[$userSkill->proficiency_level] ?? 3,
            'is_teaching' => $userSkill->type === 'owned' && $userSkill->is_willing_to_teach,
            'is_learning' => $userSkill->type === 'wanted',
            'years_of_experience' => $userSkill->years_of_experience ?? 0,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'User skill updated successfully',
            'data' => $formattedSkill
        ]);
    }

    public function removeUserSkill(Request $request, UserSkill $userSkill)
    {
        // Check if the user owns this skill
        if ($userSkill->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $userSkill->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Skill removed successfully'
        ]);
    }

    public function getUserSkills(Request $request, $userId = null)
    {
        $targetUserId = $userId ?? $request->user()->id;
        
        $userSkills = UserSkill::where('user_id', $targetUserId)
                              ->with('skill')
                              ->get();

        // Convert to frontend format
        $proficiencyMap = [
            'beginner' => 1,
            'intermediate' => 3,
            'advanced' => 4,
            'expert' => 5
        ];

        $formattedSkills = $userSkills->map(function ($userSkill) use ($proficiencyMap) {
            return [
                'id' => $userSkill->id,
                'skill' => $userSkill->skill,
                'proficiency_level' => $proficiencyMap[$userSkill->proficiency_level] ?? 3,
                'is_teaching' => $userSkill->type === 'owned' && $userSkill->is_willing_to_teach,
                'is_learning' => $userSkill->type === 'wanted',
                'years_of_experience' => $userSkill->years_of_experience ?? 0,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedSkills->values()
        ]);
    }

    public function getPopularSkills()
    {
        $skills = Skill::where('is_active', true)
                      ->orderBy('popularity_score', 'desc')
                      ->limit(20)
                      ->get();

        return response()->json([
            'status' => 'success',
            'data' => ['skills' => $skills]
        ]);
    }

    public function getSkillsByCategory()
    {
        $skills = Skill::where('is_active', true)
                      ->get()
                      ->groupBy('category');

        return response()->json([
            'status' => 'success',
            'data' => ['skills_by_category' => $skills]
        ]);
    }
}
