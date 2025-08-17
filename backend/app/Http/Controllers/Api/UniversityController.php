<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\Faculty;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UniversityController extends Controller
{
    /**
     * Get all universities with optional search
     */
    public function index(Request $request)
    {
        $query = University::with(['faculties.majors']);

        // Search by name or code
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name_en', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('name_ar', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('code', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by location
        if ($request->location) {
            $query->where('city', 'LIKE', '%' . $request->location . '%');
        }

        $universities = $query->orderBy('name_en')->get();

        // Add student counts if requested
        if ($request->include_stats) {
            $universities->load(['users' => function($query) {
                $query->selectRaw('university_id, count(*) as student_count')
                      ->groupBy('university_id');
            }]);
        }

        return response()->json([
            'success' => true,
            'data' => $universities,
        ]);
    }

    /**
     * Get a specific university with its faculties and majors
     */
    public function show($id)
    {
        $university = University::with([
            'faculties.majors',
            'users' => function($query) {
                $query->selectRaw('university_id, count(*) as student_count')
                      ->groupBy('university_id');
            }
        ])->find($id);

        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        // Add additional statistics
        $university->total_students = $university->users()->count();
        $university->total_faculties = $university->faculties()->count();
        $university->total_majors = Major::whereHas('faculty', function($query) use ($id) {
            $query->where('university_id', $id);
        })->count();

        return response()->json([
            'success' => true,
            'data' => $university,
        ]);
    }

    /**
     * Create a new university (admin only)
     */
    public function store(Request $request)
    {
        // Check if user is authenticated and has admin role
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:universities',
            'name_ar' => 'required|string|max:255|unique:universities',
            'code' => 'required|string|max:10|unique:universities',
            'type' => 'required|in:public,private,international,technical',
            'location' => 'required|string|max:255',
            'website' => 'nullable|url',
            'description' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'logo_url' => 'nullable|url',
            'established_year' => 'nullable|integer|min:1800|max:2024',
            'ranking' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $university = University::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'University created successfully',
            'data' => $university,
        ], 201);
    }

    /**
     * Update a university (admin only)
     */
    public function update(Request $request, $id)
    {
        // Check if user is authenticated and has admin role
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $university = University::find($id);

        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:universities,name,' . $id,
            'name_ar' => 'sometimes|string|max:255|unique:universities,name_ar,' . $id,
            'code' => 'sometimes|string|max:10|unique:universities,code,' . $id,
            'type' => 'sometimes|in:public,private,international,technical',
            'location' => 'sometimes|string|max:255',
            'website' => 'nullable|url',
            'description' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'logo_url' => 'nullable|url',
            'established_year' => 'nullable|integer|min:1800|max:2024',
            'ranking' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $university->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'University updated successfully',
            'data' => $university,
        ]);
    }

    /**
     * Delete a university (admin only)
     */
    public function destroy($id)
    {
        // Check if user is authenticated and has admin role
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $university = University::find($id);

        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        // Check if university has users
        if ($university->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete university with registered users'
            ], 422);
        }

        $university->delete();

        return response()->json([
            'success' => true,
            'message' => 'University deleted successfully',
        ]);
    }

    /**
     * Get faculties for a specific university
     */
    public function faculties($id)
    {
        $university = University::find($id);

        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        $faculties = $university->faculties()->with('majors')->get();

        return response()->json([
            'success' => true,
            'data' => $faculties,
        ]);
    }

    /**
     * Get majors for a specific faculty
     */
    public function facultyMajors($universityId, $facultyId)
    {
        $university = University::find($universityId);
        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        $faculty = $university->faculties()->find($facultyId);
        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty not found'
            ], 404);
        }

        $majors = $faculty->majors()->get();

        return response()->json([
            'success' => true,
            'data' => $majors,
        ]);
    }

    /**
     * Create a new faculty for a university (admin only)
     */
    public function storeFaculty(Request $request, $id)
    {
        // Check if user is authenticated and has admin role
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $university = University::find($id);
        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'description' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate faculty code within university
        $existingFaculty = Faculty::where('university_id', $id)
            ->where('code', $request->code)
            ->first();

        if ($existingFaculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty code already exists in this university'
            ], 422);
        }

        $faculty = Faculty::create([
            'university_id' => $id,
            'name' => $request->name,
            'name_ar' => $request->name_ar,
            'code' => $request->code,
            'description' => $request->description,
            'description_ar' => $request->description_ar,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Faculty created successfully',
            'data' => $faculty,
        ], 201);
    }

    /**
     * Create a new major for a faculty (admin only)
     */
    public function storeMajor(Request $request, $universityId, $facultyId)
    {
        // Check if user is authenticated and has admin role
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $university = University::find($universityId);
        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        $faculty = $university->faculties()->find($facultyId);
        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'description' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'duration_years' => 'nullable|integer|min:1|max:10',
            'degree_type' => 'nullable|in:bachelor,master,phd,diploma',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate major code within faculty
        $existingMajor = Major::where('faculty_id', $facultyId)
            ->where('code', $request->code)
            ->first();

        if ($existingMajor) {
            return response()->json([
                'success' => false,
                'message' => 'Major code already exists in this faculty'
            ], 422);
        }

        $major = Major::create([
            'faculty_id' => $facultyId,
            'name' => $request->name,
            'name_ar' => $request->name_ar,
            'code' => $request->code,
            'description' => $request->description,
            'description_ar' => $request->description_ar,
            'duration_years' => $request->duration_years,
            'degree_type' => $request->degree_type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Major created successfully',
            'data' => $major,
        ], 201);
    }

    /**
     * Get university statistics
     */
    public function statistics($id)
    {
        $university = University::find($id);

        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found'
            ], 404);
        }

        $stats = [
            'total_students' => $university->users()->count(),
            'total_faculties' => $university->faculties()->count(),
            'total_majors' => Major::whereHas('faculty', function($query) use ($id) {
                $query->where('university_id', $id);
            })->count(),
            'students_by_faculty' => $university->faculties()->withCount('users')->get()->map(function($faculty) {
                return [
                    'faculty_name' => $faculty->name,
                    'student_count' => $faculty->users_count
                ];
            }),
            'students_by_year' => $university->users()
                ->selectRaw('graduation_year, count(*) as count')
                ->groupBy('graduation_year')
                ->orderBy('graduation_year')
                ->get(),
            'user_types' => $university->users()
                ->selectRaw('user_type, count(*) as count')
                ->groupBy('user_type')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
