<?php
// app/Http/Middleware/EmployerMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmployerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isEmployer()) {
            return response()->json([
                'message' => 'Unauthorized. Employer access required.'
            ], 403);
        }

        return $next($request);
    }
}