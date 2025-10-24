<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Job",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="employer_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Développeur Laravel Senior"),
 *     @OA\Property(property="description", type="string", example="Description du poste..."),
 *     @OA\Property(property="company", type="string", example="Tech Solutions"),
 *     @OA\Property(property="location", type="string", example="Paris, France"),
 *     @OA\Property(property="salary", type="number", format="float", example=55000.00),
 *     @OA\Property(property="type", type="string", enum={"full_time", "part_time", "contract", "internship"}, example="full_time"),
 *     @OA\Property(property="category", type="string", enum={"technology", "healthcare", "education", "finance", "other"}, example="technology"),
 *     @OA\Property(property="application_deadline", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="JobRequest",
 *     type="object",
 *     required={"title", "description", "company", "location", "type", "category"},
 *     @OA\Property(property="title", type="string", example="Développeur Laravel"),
 *     @OA\Property(property="description", type="string", example="Description détaillée du poste"),
 *     @OA\Property(property="company", type="string", example="Tech Company"),
 *     @OA\Property(property="location", type="string", example="Paris, France"),
 *     @OA\Property(property="salary", type="number", format="float", example=50000),
 *     @OA\Property(property="type", type="string", enum={"full_time", "part_time", "contract", "internship"}, example="full_time"),
 *     @OA\Property(property="category", type="string", enum={"technology", "healthcare", "education", "finance", "other"}, example="technology"),
 *     @OA\Property(property="application_deadline", type="string", format="date", example="2025-12-31")
 * )
 */
class JobController extends Controller
{
    /**
     * @OA\Get(
     *     path="/jobs",
     *     summary="Lister toutes les offres d'emploi",
     *     tags={"Jobs"},
     *     @OA\Parameter(name="search", in="query", description="Recherche", @OA\Schema(type="string")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="location", in="query", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginée des offres",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Job"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Job::with('employer')->active();

        if ($request->has('search')) $query->search($request->search);
        if ($request->has('type')) $query->byType($request->type);
        if ($request->has('category')) $query->byCategory($request->category);
        if ($request->has('location')) $query->byLocation($request->location);

        $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
        $jobs = $query->paginate(10);

        return response()->json($jobs);
    }

    /**
     * @OA\Get(
     *     path="/jobs/{id}",
     *     summary="Afficher une offre d'emploi",
     *     tags={"Jobs"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails du job", @OA\JsonContent(ref="#/components/schemas/Job")),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function show($id)
    {
        $job = Job::with('employer')->active()->findOrFail($id);
        return response()->json($job);
    }

    /**
     * @OA\Post(
     *     path="/jobs",
     *     summary="Créer une nouvelle offre",
     *     tags={"Jobs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/JobRequest")),
     *     @OA\Response(response=201, description="Créé", @OA\JsonContent(ref="#/components/schemas/Job")),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
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
            return response()->json(['errors' => $validator->errors()], 422);
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

    /**
     * @OA\Put(
     *     path="/jobs/{id}",
     *     summary="Mettre à jour une offre",
     *     tags={"Jobs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/JobRequest")),
     *     @OA\Response(response=200, description="Mis à jour", @OA\JsonContent(ref="#/components/schemas/Job"))
     * )
     */
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job->update($request->all());
        return response()->json($job);
    }

    /**
     * @OA\Delete(
     *     path="/jobs/{id}",
     *     summary="Supprimer une offre",
     *     tags={"Jobs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Supprimé", @OA\JsonContent(@OA\Property(property="message", type="string", example="Job deleted successfully")))
     * )
     */
    public function destroy(Request $request, $id)
    {
        $job = Job::where('employer_id', $request->user()->id)->findOrFail($id);
        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/employer/jobs",
     *     summary="Lister les offres de l'employeur connecté",
     *     tags={"Jobs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des offres", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Job")))
     * )
     */
    public function myJobs(Request $request)
    {
        $jobs = Job::where('employer_id', $request->user()->id)
                   ->withCount('applications')
                   ->orderBy('created_at', 'desc')
                   ->paginate(10);

        return response()->json($jobs);
    }
}
