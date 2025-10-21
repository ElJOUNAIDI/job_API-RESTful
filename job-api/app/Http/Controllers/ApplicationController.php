<?php
// app/Http/Controllers/ApplicationController.php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = Application::with(['job', 'job.employer'])
            ->where('candidate_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($applications);
    }

    public function store(Request $request, $jobId)
    {
        $job = Job::active()->findOrFail($jobId);

        // Vérifier si l'utilisateur a déjà postulé
        $existingApplication = Application::where('job_id', $jobId)
            ->where('candidate_id', $request->user()->id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied for this job'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'cover_letter' => 'required|string|min:50|max:2000',
            'resume' => 'nullable|string', // Pour l'upload de fichier, vous pouvez utiliser Spatie Media Library
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $application = Application::create([
            'job_id' => $jobId,
            'candidate_id' => $request->user()->id,
            'cover_letter' => $request->cover_letter,
            'resume' => $request->resume,
        ]);

        return response()->json($application, 201);
    }

    public function show(Request $request, $id)
    {
        $application = Application::with(['job', 'job.employer'])
            ->where('candidate_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($application);
    }

    public function employerApplications(Request $request)
    {
        $applications = Application::with(['job', 'candidate'])
            ->whereHas('job', function ($query) use ($request) {
                $query->where('employer_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($applications);
    }

    public function updateStatus(Request $request, $id)
    {
        $application = Application::whereHas('job', function ($query) use ($request) {
                $query->where('employer_id', $request->user()->id);
            })
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,reviewed,accepted,rejected',
            'feedback' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $application->update([
            'status' => $request->status,
            'feedback' => $request->feedback,
        ]);

        return response()->json($application);
    }
}