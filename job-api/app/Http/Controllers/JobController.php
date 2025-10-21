<?php
// app/Http/Controllers/JobController.php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with('employer')->active();

        // Recherche
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filtres
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('location')) {
            $query->byLocation($request->location);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $jobs = $query->paginate(10);

        return response()->json($jobs);
    }

    public function show($id)
    {
        $job = Job::with('employer')->active()->findOrFail($id);
        return response()->json($job);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'company' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'type' => 'required|in:full_time,part_time,contract,internship',
            'category' => 'required|in:technology,healthcare,education,finance,other',
            'application_deadline' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $job = Job::create([
            'employer_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'company' => $request->company,
            'location' => $request->location,
            'salary' => $request->salary,
            'type' => $request->type,
            'category' => $request->category,
            'application_deadline' => $request->application_deadline,
        ]);

        return response()->json($job, 201);
    }

    public function update(Request $request, $id)
    {
        $job = Job::where('employer_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'company' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'type' => 'sometimes|required|in:full_time,part_time,contract,internship',
            'category' => 'sometimes|required|in:technology,healthcare,education,finance,other',
            'application_deadline' => 'nullable|date|after:today',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $job->update($request->all());

        return response()->json($job);
    }

    public function destroy(Request $request, $id)
    {
        $job = Job::where('employer_id', $request->user()->id)->findOrFail($id);
        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }

    public function myJobs(Request $request)
    {
        $jobs = Job::where('employer_id', $request->user()->id)
                   ->withCount('applications')
                   ->orderBy('created_at', 'desc')
                   ->paginate(10);

        return response()->json($jobs);
    }
}