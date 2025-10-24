<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="API Plateforme Emploi",
 *     version="1.0.0",
 *     description="Documentation Swagger des endpoints Admin pour la gestion des utilisateurs, offres et candidatures"
 * )
 *
 * @OA\Tag(
 *     name="Admin",
 *     description="Endpoints réservés à l'administrateur pour la gestion des utilisateurs, offres et candidatures"
 * )
 *
 *
 * @OA\Schema(
 *     schema="Statistics",
 *     type="object",
 *     @OA\Property(property="total_users", type="integer", example=150),
 *     @OA\Property(property="total_employers", type="integer", example=45),
 *     @OA\Property(property="total_candidates", type="integer", example=105),
 *     @OA\Property(property="total_jobs", type="integer", example=89),
 *     @OA\Property(property="active_jobs", type="integer", example=76),
 *     @OA\Property(property="total_applications", type="integer", example=234)
 * )
 *
 * @OA\Schema(
 *     schema="RoleUpdateRequest",
 *     type="object",
 *     required={"role"},
 *     @OA\Property(property="role", type="string", enum={"admin", "employer", "candidate"}, example="employer")
 * )
 */
class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/users",
     *     summary="Lister tous les utilisateurs (Admin seulement)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function users(Request $request)
    {
        $users = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($users);
    }

    /**
     * @OA\Put(
     *     path="/admin/users/{id}/role",
     *     summary="Modifier le rôle d'un utilisateur (Admin seulement)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RoleUpdateRequest")),
     *     @OA\Response(response=200, description="Rôle mis à jour", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function updateUserRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,employer,candidate',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->syncRoles([$request->role]);
        $user->update(['type' => $request->role]);

        return response()->json($user);
    }

    /**
     * @OA\Get(
     *     path="/admin/jobs",
     *     summary="Lister toutes les offres d'emploi (Admin seulement)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste de toutes les offres",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Job"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function allJobs(Request $request)
    {
        $jobs = Job::with('employer')
            ->withCount('applications')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($jobs);
    }

    /**
     * @OA\Get(
     *     path="/admin/applications",
     *     summary="Lister toutes les candidatures (Admin seulement)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des candidatures",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Application"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function allApplications(Request $request)
    {
        $applications = Application::with(['job', 'candidate'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($applications);
    }

    /**
     * @OA\Get(
     *     path="/admin/statistics",
     *     summary="Récupérer les statistiques globales (Admin seulement)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Statistiques", @OA\JsonContent(ref="#/components/schemas/Statistics")),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/admin/jobs/{id}",
     *     summary="Supprimer une offre d'emploi (Admin seulement)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Offre supprimée", @OA\JsonContent(@OA\Property(property="message", type="string", example="Job deleted successfully"))),
     *     @OA\Response(response=404, description="Offre non trouvée"),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function deleteJob($id)
    {
        $job = Job::findOrFail($id);
        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/admin/users/{id}",
     *     summary="Supprimer un utilisateur (Admin seulement)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Utilisateur supprimé", @OA\JsonContent(@OA\Property(property="message", type="string", example="User deleted successfully"))),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
