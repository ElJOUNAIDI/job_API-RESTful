<?php
// app/Http/Controllers/FavoriteController.php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Job;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = Favorite::with('job.employer')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($favorites);
    }

    public function toggle(Request $request, $jobId)
    {
        $job = Job::active()->findOrFail($jobId);
        $userId = $request->user()->id;

        $favorite = Favorite::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $message = 'Job removed from favorites';
        } else {
            Favorite::create([
                'user_id' => $userId,
                'job_id' => $jobId,
            ]);
            $message = 'Job added to favorites';
        }

        return response()->json(['message' => $message]);
    }

    public function check(Request $request, $jobId)
    {
        $isFavorite = Favorite::where('user_id', $request->user()->id)
            ->where('job_id', $jobId)
            ->exists();

        return response()->json(['is_favorite' => $isFavorite]);
    }
}