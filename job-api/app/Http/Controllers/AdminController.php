<?php
// app/Http/Controllers/AdminController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        $users = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($users);
    }

    public function updateUserRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,employer,candidate',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user->syncRoles([$request->role]);
        $user->update(['type' => $request->role]);

        return response()->json($user);
    }

    public function allJobs(Request $request)
    {
        $jobs = Job::with('employer')
            ->withCount('applications')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($jobs);
    }

    public function allApplications(Request $request)
    {
        $applications = Application::with(['job', 'candidate'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($applications);
    }

    public function statistics()
    {
        $stats = [
            'total_users' => User::count(),
            'total_employers' => User::where('type', 'employer')->count(),
            'total_candidates' => User::where('type', 'candidate')->count(),
            'total_jobs' => Job::count(),
            'active_jobs' => Job::active()->count(),
            'total_applications' => Application::count(),
        ];

        return response()->json($stats);
    }
}