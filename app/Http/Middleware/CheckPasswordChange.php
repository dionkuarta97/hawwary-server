<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponse;

class CheckPasswordChange
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Unauthorized.', 401);
        }

        // Cek apakah user sudah di-delete
        if ($user->deleted_at) {
            return $this->errorResponse('User tidak aktif.', 401);
        }

        // Cek apakah ada token dan password sudah diubah
        try {
            $tokenCreatedAt = $user->currentAccessToken()->created_at;
            $passwordChangedAt = $user->password_changed_at;

            if ($passwordChangedAt && $tokenCreatedAt->lt($passwordChangedAt)) {
                return $this->errorResponse('Token tidak valid. Password telah diubah.', 401);
            }
        } catch (\Exception $e) {
            // Jika tidak ada token, kirim error response
            return $this->errorResponse('Token tidak valid.', 401);
        }

        return $next($request);
    }
}
