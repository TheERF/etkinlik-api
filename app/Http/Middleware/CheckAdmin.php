<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user || !$user->is_admin) {
                return response()->json([
                    'message' => 'Unauthorized. Admin access only.'
                ], Response::HTTP_FORBIDDEN);
            }

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Token invalid or expired.',
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
