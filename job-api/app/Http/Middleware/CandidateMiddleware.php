<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CandidateMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if ($request->user()->type !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized. Candidate access required.'
            ], 403);
        }

        return $next($request);
    }
}