<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use App\Models\UserProfile;
use App\Models\College;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // Show user profile
    public function show(User $user)
    {
        $currentUser = Auth::user();
        
        // Load relationships
        $user->load(['college', 'major', 'profile']);
        
        // Check friendship status
        $friendship = null;
        $friendshipStatus = 'none'; // none, pending_sent, pending_received, friends, blocked
        
        if ($currentUser->user_id !== $user->user_id) {
            $friendship = Friendship::where(function($query) use ($currentUser, $user) {
                $query->where('user1_id', $currentUser->user_id)
                      ->where('user2_id', $user->user_id);
            })->orWhere(function($query) use ($currentUser, $user) {
                $query->where('user1_id', $user->user_id)
                      ->where('user2_id', $currentUser->user_id);
            })->first();
            
            if ($friendship) {
                if ($friendship->status === 'accepted') {
                    $friendshipStatus = 'friends';
                } elseif ($friendship->status === 'pending') {
                    if ($friendship->created_by === $currentUser->user_id) {
                        $friendshipStatus = 'pending_sent';
                    } else {
                        $friendshipStatus = 'pending_received';
                    }
                } elseif ($friendship->status === 'blocked') {
                    $friendshipStatus = 'blocked';
                }
            }
        }
        
        // Get user's stats and activities
        $stats = [
            'friends_count' => $this->getFriendsCount($user),
            'resources_count' => $user->resources()->where('is_approved', true)->count(),
            'forum_posts_count' => $user->forumPosts()->count(),
            'achievements_count' => $user->achievements()->count(),
        ];
        
        // Get recent activities
        $recentResources = $user->resources()->where('is_approved', true)
                                           ->with('subject.major')
                                           ->latest()->take(3)->get();
        
        $recentForumPosts = $user->forumPosts()->with('thread.category')
                                              ->latest()->take(3)->get();
        
        $achievements = $user->achievements()->latest()->take(5)->get();
        
        // Get mutual friends if viewing another user
        $mutualFriends = [];
        if ($currentUser->user_id !== $user->user_id) {
            $mutualFriends = $this->getMutualFriends($currentUser, $user);
        }
        
        return view('profile.show', compact(
            'user', 
            'friendship', 
            'friendshipStatus',
            'stats',
            'recentResources',
            'recentForumPosts',
            'achievements',
            'mutualFriends'
        ));
    }

    // Edit profile form
    public function edit()
    {
        $user = Auth::user();
        $user->load(['college', 'major', 'profile']);
        
        $colleges = College::orderBy('name')->get();
        $majors = Major::orderBy('name')->get();
        
        return view('profile.edit', compact('user', 'colleges', 'majors'));
    }

    // Update profile
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'bio' => 'nullable|string|max:500',
            'college_id' => 'nullable|exists:colleges,college_id',
            'major_id' => 'nullable|exists:majors,major_id',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'privacy_profile' => 'required|in:public,friends,private',
            'privacy_email' => 'required|in:public,friends,private',
            'privacy_phone' => 'required|in:public,friends,private',
        ]);

        // Handle profile picture upload
        $profilePicPath = $user->profile_pic;
        if ($request->hasFile('profile_pic')) {
            // Delete old profile picture
            if ($profilePicPath && Storage::disk('public')->exists($profilePicPath)) {
                Storage::disk('public')->delete($profilePicPath);
            }
            
            $profilePicPath = $request->file('profile_pic')->store('uploads/profiles', 'public');
        }

        // Update user basic info
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'bio' => $request->bio,
            'profile_pic' => $profilePicPath,
            'college_id' => $request->college_id,
            'major_id' => $request->major_id,
        ]);

        // Update or create user profile
        UserProfile::updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'phone' => $request->phone,
                'website' => $request->website,
                'facebook' => $request->facebook,
                'twitter' => $request->twitter,
                'linkedin' => $request->linkedin,
                'privacy_profile' => $request->privacy_profile,
                'privacy_email' => $request->privacy_email,
                'privacy_phone' => $request->privacy_phone,
            ]
        );

        return redirect()->route('profile.show', $user)->with('success', 'Profile updated successfully!');
    }

    // Search users
    public function search(Request $request)
    {
        $query = $request->get('q');
        $college = $request->get('college');
        $major = $request->get('major');
        
        $users = User::query()
            ->with(['college', 'major', 'profile'])
            ->where('user_id', '!=', Auth::id());

        if ($query) {
            $users->where(function($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhere('username', 'LIKE', "%{$query}%")
                  ->orWhere('bio', 'LIKE', "%{$query}%");
            });
        }

        if ($college) {
            $users->where('college_id', $college);
        }

        if ($major) {
            $users->where('major_id', $major);
        }

        $users = $users->paginate(12);
        
        $colleges = College::orderBy('name')->get();
        $majors = Major::orderBy('name')->get();

        return view('profile.search', compact('users', 'query', 'colleges', 'majors', 'college', 'major'));
    }

    // Send friend request
    public function sendFriendRequest(User $user)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->user_id === $user->user_id) {
            return back()->with('error', 'You cannot send a friend request to yourself.');
        }
        
        $result = $currentUser->sendFriendRequest($user->user_id);
        
        if ($result) {
            return back()->with('success', 'Friend request sent successfully!');
        } else {
            return back()->with('error', 'Friend request already exists or there was an error.');
        }
    }

    // Accept friend request
    public function acceptFriendRequest($friendshipId)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->acceptFriendRequest($friendshipId)) {
            return back()->with('success', 'Friend request accepted!');
        } else {
            return back()->with('error', 'Unable to accept friend request.');
        }
    }

    // Decline friend request
    public function declineFriendRequest($friendshipId)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->declineFriendRequest($friendshipId)) {
            return back()->with('success', 'Friend request declined.');
        } else {
            return back()->with('error', 'Unable to decline friend request.');
        }
    }

    // Remove friend
    public function removeFriend(User $user)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->removeFriend($user->user_id)) {
            return back()->with('success', 'Friend removed successfully.');
        } else {
            return back()->with('error', 'Unable to remove friend.');
        }
    }

    // Get friends list
    public function friends(User $user = null)
    {
        $targetUser = $user ?? Auth::user();
        
        $friends = Friendship::where('status', Friendship::STATUS_ACCEPTED)
            ->where(function($query) use ($targetUser) {
                $query->where('user1_id', $targetUser->user_id)
                      ->orWhere('user2_id', $targetUser->user_id);
            })
            ->with(['user1.college', 'user1.major', 'user2.college', 'user2.major'])
            ->paginate(12);

        return view('profile.friends', compact('targetUser', 'friends'));
    }

    // Get friend requests
    public function friendRequests()
    {
        $currentUser = Auth::user();
        
        $receivedRequests = Friendship::where('user2_id', $currentUser->user_id)
            ->where('status', Friendship::STATUS_PENDING)
            ->where('created_by', '!=', $currentUser->user_id)
            ->with('user1.college', 'user1.major')
            ->latest()
            ->get();
            
        $sentRequests = Friendship::where('user1_id', $currentUser->user_id)
            ->where('status', Friendship::STATUS_PENDING)
            ->where('created_by', $currentUser->user_id)
            ->with('user2.college', 'user2.major')
            ->latest()
            ->get();

        return view('profile.friend-requests', compact('receivedRequests', 'sentRequests'));
    }

    // Helper methods
    private function getFriendsCount(User $user)
    {
        return Friendship::where('status', Friendship::STATUS_ACCEPTED)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->user_id)
                      ->orWhere('user2_id', $user->user_id);
            })->count();
    }

    private function getMutualFriends(User $user1, User $user2)
    {
        $user1Friends = $this->getUserFriendIds($user1);
        $user2Friends = $this->getUserFriendIds($user2);
        
        $mutualFriendIds = $user1Friends->intersect($user2Friends);
        
        return User::whereIn('user_id', $mutualFriendIds)
                   ->with('college', 'major')
                   ->take(6)
                   ->get();
    }

    private function getUserFriendIds(User $user)
    {
        return Friendship::where('status', Friendship::STATUS_ACCEPTED)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->user_id)
                      ->orWhere('user2_id', $user->user_id);
            })
            ->get()
            ->map(function($friendship) use ($user) {
                return $friendship->user1_id === $user->user_id 
                    ? $friendship->user2_id 
                    : $friendship->user1_id;
            });
    }
} 
            'friendshipStatus', 
            'achievements', 
            'recentResources'
        ));
    }

    public function edit()
    {
        $user = Auth::user();
        $user->load(['college', 'major', 'profile']);
        
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
            'bio' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:500',
            'linkedin' => 'nullable|url|max:500',
            'github' => 'nullable|url|max:500',
            'privacy_level' => 'required|in:public,friends,private',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle profile picture upload
        $profilePicPath = $user->profile_pic;
        if ($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $filename = 'profile_' . $user->user_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/profiles'), $filename);
            $profilePicPath = '/uploads/profiles/' . $filename;
        }

        // Update user basic info
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'bio' => $request->bio,
            'profile_pic' => $profilePicPath,
        ]);

        // Update or create profile
        $user->profile()->updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'bio' => $request->bio,
                'phone' => $request->phone,
                'website' => $request->website,
                'linkedin' => $request->linkedin,
                'github' => $request->github,
                'privacy_level' => $request->privacy_level,
                'show_email' => $request->boolean('show_email'),
                'show_phone' => $request->boolean('show_phone'),
            ]
        );

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    public function sendFriendRequest(User $user)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->user_id === $user->user_id) {
            return redirect()->back()->with('error', 'You cannot send a friend request to yourself.');
        }

        // Check if friendship already exists
        $existingFriendship = Friendship::where(function($query) use ($currentUser, $user) {
            $query->where('user1_id', $currentUser->user_id)
                  ->where('user2_id', $user->user_id);
        })->orWhere(function($query) use ($currentUser, $user) {
            $query->where('user1_id', $user->user_id)
                  ->where('user2_id', $currentUser->user_id);
        })->first();

        if ($existingFriendship) {
            return redirect()->back()->with('error', 'Friend request already exists or you are already friends.');
        }

        // Create friend request
        Friendship::create([
            'user1_id' => $currentUser->user_id,
            'user2_id' => $user->user_id,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Friend request sent successfully!');
    }

    public function acceptFriendRequest(Friendship $friendship)
    {
        $currentUser = Auth::user();
        
        // Verify the current user is the recipient
        if ($friendship->user2_id !== $currentUser->user_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $friendship->update(['status' => 'accepted']);

        return redirect()->back()->with('success', 'Friend request accepted!');
    }

    public function declineFriendRequest(Friendship $friendship)
    {
        $currentUser = Auth::user();
        
        // Verify the current user is the recipient
        if ($friendship->user2_id !== $currentUser->user_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $friendship->delete();

        return redirect()->back()->with('success', 'Friend request declined.');
    }
}
