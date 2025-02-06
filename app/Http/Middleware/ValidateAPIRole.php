<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateAPIRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, String $api_role): Response
    {
        $user = Auth::user();

        if($user->role == $api_role && $user->status == '1') {
            return $next($request);
        }

        Auth::user()->token()->revoke();

        return response()->json([
            'http_status' => 400,
            'http_status_message' => 'Unauthorized',
            'message' => 'Access failed',
            'error' => ['info' => 'Invalid role or account']
        ], 400);
    }
}
