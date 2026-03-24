<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Admins bypass subscription check
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $office = $user->office;

        if (!$office) {
            return response()->json(['message' => 'Usuário sem escritório vinculado.'], 403);
        }

        // Check if office is active
        if (!$office->is_active) {
            return response()->json(['message' => 'Escritório inativo.'], 403);
        }

        // Check subscription
        $subscription = $office->subscription;

        if (!$subscription) {
            return response()->json(['message' => 'Nenhuma assinatura encontrada.'], 403);
        }

        if ($subscription->status !== 'active') {
            $statusMessages = [
                'cancelled' => 'Assinatura cancelada.',
                'expired' => 'Assinatura expirada.',
                'trial' => 'Período de trial encerrado.',
            ];
            return response()->json([
                'message' => $statusMessages[$subscription->status] ?? 'Assinatura inativa.'
            ], 403);
        }

        // Check if subscription has ended
        if ($subscription->ends_at && now()->isAfter($subscription->ends_at)) {
            return response()->json(['message' => 'Assinatura expirada.'], 403);
        }

        return $next($request);
    }
}
