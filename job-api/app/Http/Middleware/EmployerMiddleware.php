<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmployerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Vérifier que l'utilisateur est authentifié
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Vérifier que l'utilisateur est un employeur
        if ($request->user()->type !== 'employer') {
            return response()->json([
                'message' => 'Unauthorized. Employer access required.'
            ], 403);
        }

        return $next($request);
    }
}