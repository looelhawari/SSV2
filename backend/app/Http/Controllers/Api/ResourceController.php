<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResourceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all resources with filters and search
     */
    public function index(Request $request)
    {
        $query = Resource::with(['user', 'skill', 'university', 'faculty', 'major', 'reviews'])
            ->where('status', 'approved');

        // Search by title or description
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('tags', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter by resource type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by skill
        if ($request->skill_id) {
            $query->where('skill_id', $request->skill_id);
        }

        // Filter by university
        if ($request->university_id) {
            $query->where('university_id', $request->university_id);
        }

        // Filter by faculty
        if ($request->faculty_id) {
            $query->where('faculty_id', $request->faculty_id);
        }

        // Filter by major
        if ($request->major_id) {
            $query->where('major_id', $request->major_id);
        }

        // Filter by difficulty level
        if ($request->difficulty_level) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        // Filter by language
        if ($request->language) {
            $query->where('language', $request->language);
        }

        // Filter by minimum rating
        if ($request->min_rating) {
            $query->whereHas('reviews', function($q) use ($request) {
                $q->havingRaw('AVG(rating) >= ?', [$request->min_rating]);
            });
        }

        // Sort options
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        if ($sortBy === 'rating') {
            $query->leftJoin('reviews', 'resources.id', '=', 'reviews.resource_id')
                  ->groupBy('resources.id')
                  ->orderByRaw('AVG(reviews.rating) ' . $sortOrder);
        } elseif ($sortBy === 'downloads') {
            $query->orderBy('download_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $resources = $query->paginate($request->per_page ?? 15);

        // Add average rating and download count to each resource
        $resources->getCollection()->transform(function ($resource) {
            $resource->average_rating = $resource->reviews()->avg('rating') ?? 0;
            $resource->reviews_count = $resource->reviews()->count();
            return $resource;
        });

        return response()->json([
            'success' => true,
            'data' => $resources,
        ]);
    }

    /**
     * Create a new resource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'type' => 'required|in:document,video,link,code,quiz,presentation,book,article',
            'skill_id' => 'required|exists:skills,id',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'language' => 'required|in:english,arabic,mixed',
            'content_url' => 'required_if:type,link,video|url',
            'file' => 'required_unless:type,link,video|file|max:51200', // 50MB max
            'university_id' => 'nullable|exists:universities,id',
            'faculty_id' => 'nullable|exists:faculties,id',
            'major_id' => 'nullable|exists:majors,id',
            'tags' => 'nullable|string|max:500',
            'is_free' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Handle file upload
        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('resources', $fileName, 'public');
            $fileSize = $file->getSize();
        }

        $resource = Resource::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'skill_id' => $request->skill_id,
            'difficulty_level' => $request->difficulty_level,
            'language' => $request->language,
            'content_url' => $request->content_url,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'university_id' => $request->university_id,
            'faculty_id' => $request->faculty_id,
            'major_id' => $request->major_id,
            'tags' => $request->tags,
            'is_free' => $request->is_free ?? true,
            'price' => $request->price,
            'duration_minutes' => $request->duration_minutes,
            'status' => 'pending', // Will be approved by moderators
        ]);

        $resource->load(['user', 'skill', 'university', 'faculty', 'major']);

        // Award XP for sharing resource
        $user->addXP(15, 'Shared educational resource');

        return response()->json([
            'success' => true,
            'message' => 'Resource uploaded successfully and is pending approval',
            'data' => $resource,
        ], 201);
    }

    /**
     * Get a specific resource
     */
    public function show($id)
    {
        $resource = Resource::with(['user', 'skill', 'university', 'faculty', 'major', 'reviews.user'])
            ->find($id);

        if (!$resource) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }

        // Check if resource is approved or user is the owner/admin
        $user = Auth::user();
        if ($resource->status !== 'approved' && 
            $resource->user_id !== $user->id && 
            !$user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not available'
            ], 404);
        }

        // Increment view count
        $resource->increment('view_count');

        // Add computed fields
        $resource->average_rating = $resource->reviews()->avg('rating') ?? 0;
        $resource->reviews_count = $resource->reviews()->count();

        return response()->json([
            'success' => true,
            'data' => $resource,
        ]);
    }

    /**
     * Update a resource (only by owner or admin)
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }

        // Check permissions
        if ($resource->user_id !== $user->id && !$user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this resource'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:2000',
            'type' => 'sometimes|in:document,video,link,code,quiz,presentation,book,article',
            'skill_id' => 'sometimes|exists:skills,id',
            'difficulty_level' => 'sometimes|in:beginner,intermediate,advanced',
            'language' => 'sometimes|in:english,arabic,mixed',
            'content_url' => 'nullable|url',
            'university_id' => 'nullable|exists:universities,id',
            'faculty_id' => 'nullable|exists:faculties,id',
            'major_id' => 'nullable|exists:majors,id',
            'tags' => 'nullable|string|max:500',
            'is_free' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:pending,approved,rejected', // Admin/moderator only
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Only admin/moderator can change status
        if ($request->has('status') && !$user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to change resource status'
            ], 403);
        }

        $resource->fill($request->except(['file']));

        // Handle new file upload
        if ($request->hasFile('file')) {
            // Delete old file
            if ($resource->file_path) {
                Storage::disk('public')->delete($resource->file_path);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('resources', $fileName, 'public');
            $fileSize = $file->getSize();

            $resource->file_path = $filePath;
            $resource->file_name = $fileName;
            $resource->file_size = $fileSize;
        }

        $resource->save();
        $resource->load(['user', 'skill', 'university', 'faculty', 'major']);

        return response()->json([
            'success' => true,
            'message' => 'Resource updated successfully',
            'data' => $resource,
        ]);
    }

    /**
     * Delete a resource
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }

        // Check permissions
        if ($resource->user_id !== $user->id && !$user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this resource'
            ], 403);
        }

        // Delete file if exists
        if ($resource->file_path) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $resource->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resource deleted successfully',
        ]);
    }

    /**
     * Download a resource file
     */
    public function download($id)
    {
        $user = Auth::user();
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }

        if ($resource->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Resource not available for download'
            ], 403);
        }

        if (!$resource->file_path || !Storage::disk('public')->exists($resource->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        // Check if resource is paid and user has access
        if (!$resource->is_free && $resource->user_id !== $user->id) {
            // Here you would check if user has purchased access
            // For now, we'll allow free access
        }

        // Increment download count
        $resource->increment('download_count');

        // Award XP to resource owner
        $resource->user->addXP(2, 'Resource downloaded');

        return Storage::disk('public')->download($resource->file_path, $resource->file_name);
    }

    /**
     * Get user's resources
     */
    public function myResources(Request $request)
    {
        $user = Auth::user();
        
        $query = Resource::where('user_id', $user->id)
            ->with(['skill', 'university', 'faculty', 'major', 'reviews']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $resources = $query->latest()->paginate($request->per_page ?? 15);

        // Add computed fields
        $resources->getCollection()->transform(function ($resource) {
            $resource->average_rating = $resource->reviews()->avg('rating') ?? 0;
            $resource->reviews_count = $resource->reviews()->count();
            return $resource;
        });

        return response()->json([
            'success' => true,
            'data' => $resources,
        ]);
    }

    /**
     * Get popular resources
     */
    public function popular(Request $request)
    {
        $query = Resource::with(['user', 'skill', 'reviews'])
            ->where('status', 'approved');

        // Get timeframe (default: all time)
        $timeframe = $request->timeframe ?? 'all'; // week, month, year, all

        if ($timeframe !== 'all') {
            $date = match($timeframe) {
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'year' => now()->subYear(),
                default => now()->subMonth()
            };
            $query->where('created_at', '>=', $date);
        }

        // Sort by download count and rating
        $resources = $query->withCount('reviews')
            ->orderByRaw('(download_count * 0.7) + (view_count * 0.2) + (reviews_count * 0.1) DESC')
            ->limit($request->limit ?? 20)
            ->get();

        // Add computed fields
        $resources->transform(function ($resource) {
            $resource->average_rating = $resource->reviews()->avg('rating') ?? 0;
            return $resource;
        });

        return response()->json([
            'success' => true,
            'data' => $resources,
        ]);
    }

    /**
     * Get resource statistics
     */
    public function stats()
    {
        $user = Auth::user();

        $stats = [
            'total_resources' => $user->resources()->count(),
            'approved_resources' => $user->resources()->where('status', 'approved')->count(),
            'pending_resources' => $user->resources()->where('status', 'pending')->count(),
            'total_downloads' => $user->resources()->sum('download_count'),
            'total_views' => $user->resources()->sum('view_count'),
            'average_rating' => $user->resources()->join('reviews', 'resources.id', '=', 'reviews.resource_id')->avg('reviews.rating') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
