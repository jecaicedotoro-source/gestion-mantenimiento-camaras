<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Invalida accesos existentes y desafíos 2FA de cuentas desactivadas. */
final class EnsureUserIsActive
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $pendingUserId = $request->hasSession() ? $request->session()->get('login.id') : null;
        $pendingUserId = is_int($pendingUserId) || is_string($pendingUserId) ? $pendingUserId : null;

        // Consultar otra vez evita confiar en un usuario cacheado por el guard.
        $currentUser = $user instanceof User ? User::query()->find($user->id) : null;
        $inactiveUser = $user instanceof User && ($currentUser === null || ! $currentUser->is_active);
        $pendingUser = $pendingUserId !== null ? User::query()->find($pendingUserId) : null;
        $challengeVersion = $request->hasSession() ? $request->session()->get('auth.challenge_version') : null;
        $inactiveChallenge = $pendingUserId !== null && ($pendingUser === null || ! $pendingUser->is_active
            || ($challengeVersion === null ? $pendingUser->auth_version > 0 : (int) $challengeVersion !== $pendingUser->auth_version));
        $revokedSession = false;

        if ($currentUser !== null && $request->hasSession()) {
            $version = $request->session()->get('auth.account_version');
            // Solo la versión inicial admite sesiones previas a esta migración.
            $revokedSession = $version === null ? $currentUser->auth_version > 0 : (int) $version !== $currentUser->auth_version;
            if (! $revokedSession) {
                $request->session()->put('auth.account_version', $currentUser->auth_version);
            }
        }

        if ($inactiveUser || $inactiveChallenge || $revokedSession) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $message = $inactiveUser || $inactiveChallenge
                ? 'Tu cuenta está desactivada. Contacta con un administrador.'
                : 'Tu acceso ha sido revocado. Inicia sesión de nuevo.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
