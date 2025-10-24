<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Job;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Favorite",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="job_id", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="job", ref="#/components/schemas/Job")
 * )
 */
class FavoriteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/favorites",
     *     summary="Lister les offres favorites du candidat connecté",
     *     tags={"Favorites"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginée des offres favorites",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Favorite")),
     *             @OA\Property(property="total", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $favorites = Favorite::with('job.employer')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($favorites);
    }

    /**
     * @OA\Post(
     *     path="/jobs/{id}/favorite",
     *     summary="Ajouter ou retirer une offre d'emploi des favoris",
     *     tags={"Favorites"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'offre d'emploi",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut du favori modifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job added to favorites")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Offre d'emploi non trouvée"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/jobs/{id}/favorite/check",
     *     summary="Vérifier si une offre est dans les favoris de l'utilisateur",
     *     tags={"Favorites"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'offre d'emploi",
     *         @OA\Schema(type="integer", example=8)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résultat de la vérification",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_favorite", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function check(Request $request, $jobId)
    {
        $isFavorite = Favorite::where('user_id', $request->user()->id)
            ->where('job_id', $jobId)
            ->exists();

        return response()->json(['is_favorite' => $isFavorite]);
    }
}
