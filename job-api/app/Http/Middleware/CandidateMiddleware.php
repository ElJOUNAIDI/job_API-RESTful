<?php
// app/Http/Middleware/CandidateMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CandidateMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isCandidate()) {
            return response()->json([
                'message' => 'Unauthorized. Candidate access required.'
            ], 403);
        }

        return $next($request);
    }
}