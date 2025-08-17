<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'user_type' => 'required|in:student,mentor',
            'student_id' => 'nullable|string|max:50',
            'university_id' => 'nullable|exists:universities,id',
            'faculty_id' => 'nullable|exists:faculties,id',
            'major_id' => 'nullable|exists:majors,id',
            'year_of_study' => 'nullable|integer|min:1|max:7',
            'gpa' => 'nullable|numeric|min:0|max:4',
            'preferred_language' => 'required|in:arabic,english',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'user_type' => $request->user_type,
            'student_id' => $request->student_id,
            'university_id' => $request->university_id,
            'faculty_id' => $request->faculty_id,
            'major_id' => $request->major_id,
            'year_of_study' => $request->year_of_study,
            'gpa' => $request->gpa,
            'preferred_language' => $request->preferred_language,
            'languages' => ['arabic', 'english'], // Default both languages
        ]);

        // Create user profile
        UserProfile::create([
            'user_id' => $user->id,
            'availability_status' => 'available',
            'is_mentor' => $request->user_type === 'mentor',
        ]);

        // Assign default role
        $user->assignRole($request->user_type);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'user' => $user->load(['university', 'faculty', 'major', 'profile']),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = Auth::user();
        
        // Update last active timestamp
        $user->update(['last_active_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load(['university', 'faculty', 'major', 'profile']),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $request->user()->load([
                    'university', 
                    'faculty', 
                    'major', 
                    'profile',
                    'skills.skill',
                    'skillSwapsAsRequester',
                    'skillSwapsAsProvider',
                    'mentorshipsAsMentor',
                    'mentorshipsAsMentee'
                ])
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'date_of_birth' => 'sometimes|nullable|date',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'bio_en' => 'sometimes|nullable|string|max:500',
            'bio_ar' => 'sometimes|nullable|string|max:500',
            'location' => 'sometimes|nullable|string|max:255',
            'timezone' => 'sometimes|nullable|string|max:50',
            'preferred_contact_method' => 'sometimes|nullable|in:email,phone,platform',
            'website' => 'sometimes|nullable|url|max:255',
            'linkedin' => 'sometimes|nullable|string|max:255',
            'github' => 'sometimes|nullable|string|max:255',
            'twitter' => 'sometimes|nullable|string|max:255',
            'years_of_experience' => 'sometimes|nullable|integer|min:0|max:50',
            'education_level' => 'sometimes|nullable|in:high_school,undergraduate,graduate,postgraduate,phd',
            'graduation_year' => 'sometimes|nullable|integer|min:1950|max:2030',
            'languages_spoken' => 'sometimes|nullable|array',
            'languages_spoken.*' => 'string|max:50',
            'privacy_settings' => 'sometimes|nullable|array',
            'privacy_settings.show_email' => 'sometimes|boolean',
            'privacy_settings.show_phone' => 'sometimes|boolean',
            'privacy_settings.show_location' => 'sometimes|boolean',
            'privacy_settings.show_university' => 'sometimes|boolean',
            'university_id' => 'sometimes|nullable|exists:universities,id',
            'faculty_id' => 'sometimes|nullable|exists:faculties,id',
            'major_id' => 'sometimes|nullable|exists:majors,id',
            'year_of_study' => 'sometimes|nullable|integer|min:1|max:7',
            'gpa' => 'sometimes|nullable|numeric|min:0|max:4',
            'preferred_language' => 'sometimes|in:arabic,english',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle languages_spoken - convert to languages field
        $updateData = $validator->validated();
        if (isset($updateData['languages_spoken'])) {
            $updateData['languages'] = $updateData['languages_spoken'];
            unset($updateData['languages_spoken']);
        }

        $user->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->fresh()->load(['university', 'faculty', 'major', 'profile'])
            ]
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully'
        ]);
    }
}
