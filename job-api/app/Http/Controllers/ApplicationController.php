<?php
// app/Http/Controllers/ApplicationController.php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Application",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="job_id", type="integer", example=1),
 *     @OA\Property(property="candidate_id", type="integer", example=2),
 *     @OA\Property(property="cover_letter", type="string", example="Lettre de motivation..."),
 *     @OA\Property(property="resume", type="string", example="cv.pdf"),
 *     @OA\Property(property="status", type="string", enum={"pending", "reviewed", "accepted", "rejected"}, example="pending"),
 *     @OA\Property(property="feedback", type="string", example="Feedback de l'employeur"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="job", ref="#/components/schemas/Job"),
 *     @OA\Property(property="candidate", ref="#/components/schemas/User")
 * )
 *
 * @OA\Schema(
 *     schema="ApplicationRequest",
 *     type="object",
 *     required={"cover_letter"},
 *     @OA\Property(property="cover_letter", type="string", minLength=50, maxLength=2000, example="Je suis très intéressé par cette opportunité..."),
 *     @OA\Property(property="resume", type="string", maxLength=255, example="mon_cv.pdf")
 * )
 *
 * @OA\Schema(
 *     schema="ApplicationStatusRequest",
 *     type="object",
 *     required={"status"},
 *     @OA\Property(property="status", type="string", enum={"pending", "reviewed", "accepted", "rejected"}, example="reviewed"),
 *     @OA\Property(property="feedback", type="string", maxLength=1000, example="Votre profil a été retenu pour un entretien")
 * )
 */
class ApplicationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/candidate/applications",
     *     summary="Lister les candidatures du candidat connecté",
     *     tags={"Applications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginée des candidatures du candidat",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Application"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé - Candidat requis")
     * )
     */
    public function index(Request $request)
    {
        $applications = Application::with(['job', 'job.employer'])
            ->where('candidate_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($applications);
    }

    /**
     * @OA\Post(
     *     path="/jobs/{id}/apply",
     *     summary="Postuler à une offre d'emploi",
     *     tags={"Applications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du job à postuler",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ApplicationRequest")),
     *     @OA\Response(response=201, description="Candidature soumise", @OA\JsonContent(ref="#/components/schemas/Application")),
     *     @OA\Response(response=404, description="Offre non trouvée"),
     *     @OA\Response(response=422, description="Déjà postulé ou erreur de validation"),
     *     @OA\Response(response=403, description="Accès refusé - Candidat requis")
     * )
     */
    public function store(Request $request, $jobId)
    {
        $job = Job::active()->findOrFail($jobId);

        // Vérifier si le candidat a déjà postulé
        $existingApplication = Application::where('job_id', $jobId)
            ->where('candidate_id', $request->user()->id)
            ->first();

        if ($existingApplication) {
            return response()->json(['message' => 'You have already applied for this job'], 422);
        }

        $validator = Validator::make($request->all(), [
            'cover_letter' => 'required|string|min:50|max:2000',
            'resume' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $application = Application::create([
            'job_id' => $jobId,
            'candidate_id' => $request->user()->id,
            'cover_letter' => $request->cover_letter,
            'resume' => $request->resume,
        ]);

        return response()->json($application, 201);
    }

    /**
     * @OA\Get(
     *     path="/candidate/applications/{id}",
     *     summary="Afficher les détails d'une candidature spécifique du candidat",
     *     tags={"Applications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la candidature", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de la candidature", @OA\JsonContent(ref="#/components/schemas/Application")),
     *     @OA\Response(response=404, description="Candidature non trouvée"),
     *     @OA\Response(response=403, description="Accès refusé - Candidat requis")
     * )
     */
    public function show(Request $request, $id)
    {
        $application = Application::with(['job', 'job.employer'])
            ->where('candidate_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($application);
    }

    /**
     * @OA\Get(
     *     path="/employer/applications",
     *     summary="Lister toutes les candidatures reçues par les offres de l'employeur connecté",
     *     tags={"Applications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginée des candidatures reçues",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Application"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé - Employeur requis")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/applications/{id}/status",
     *     summary="Mettre à jour le statut d'une candidature (Employeur uniquement)",
     *     tags={"Applications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la candidature", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ApplicationStatusRequest")),
     *     @OA\Response(response=200, description="Statut mis à jour", @OA\JsonContent(ref="#/components/schemas/Application")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Candidature non trouvée")
     * )
     */
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $application->update([
            'status' => $request->status,
            'feedback' => $request->feedback,
        ]);

        return response()->json($application);
    }
}
