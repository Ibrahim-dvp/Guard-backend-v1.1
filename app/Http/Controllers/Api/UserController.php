<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);

        $users = $this->userService->getAllUsers();
        
        // Manual pagination for consistent API response
        $total = $users->count();
        $totalPages = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;
        $paginatedUsers = $users->slice($offset, $pageSize)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => UserResource::collection($paginatedUsers),
                'total' => $total,
                'page' => (int) $page,
                'pageSize' => (int) $pageSize,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        $user = $this->userService->createUser($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'User created successfully.',
        ], 201);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['roles', 'organization'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $updatedUser = $this->userService->updateUser($user, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($updatedUser),
            'message' => 'User updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $this->userService->deleteUser($user);
        
        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    /**
     * Get team members for a specific manager.
     */
    public function getTeamMembers(Request $request, string $managerId)
    {
        $manager = User::findOrFail($managerId);
        
        // Verify access
        if (!Auth::user()->hasRole(['Admin', 'Group Director']) && Auth::id() !== $managerId) {
            abort(403, 'Unauthorized');
        }
        
        // Get team members (users created by this manager or in same organization with Sales Agent role)
        $teamMembers = User::where(function($q) use ($manager) {
            $q->where('created_by', $manager->id)
              ->orWhere('organization_id', $manager->organization_id);
        })->whereHas('roles', function($q) {
            $q->where('name', 'Sales Agent');
        })->with('roles')->get();
        
        return response()->json([
            'success' => true,
            'data' => UserResource::collection($teamMembers),
            'message' => 'Success',
        ]);
    }

    /**
     * Get managers with capacity.
     */
    public function getManagersWithCapacity(Request $request)
    {
        $user = Auth::user();
        
        $managersQuery = User::whereHas('roles', function($q) {
            $q->where('name', 'Sales Manager');
        });
        
        // Apply role-based filtering
        if ($user->hasRole(['Admin', 'Group Director'])) {
            // Admin and Group Director see all managers
        } elseif ($user->hasRole('Partner Director')) {
            // Partner Director sees only their organization managers
            $managersQuery->where('organization_id', $user->organization_id);
        } else {
            // Others see no managers or limited access
            $managersQuery->where('id', $user->id);
        }
        
        $managers = $managersQuery->with('roles')->get();
        
        return response()->json([
            'success' => true,
            'data' => UserResource::collection($managers),
        ]);
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user)
    {
        $this->authorize('update', $user);
        
        $user->is_active = !$user->is_active;
        $user->save();
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['roles', 'organization'])),
            'message' => 'User status updated successfully.',
        ]);
    }
}
