<?php

namespace App\Core\Http\Middleware;

use App\Core\Application\Auth\PermissionChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access when the authenticated user does not hold the required permission
 * in the current tenant.
 *
 * Usage in routes: ->middleware('permission:resource.action')
 */
class EnsurePermission
{
    public function __construct(private readonly PermissionChecker $checker) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || !$this->checker->check($user, $permission)) {
            return response()->json([
                'message' => 'Forbidden.',
                'code' => 'PERMISSION_DENIED',
            ], 403);
        }

        return $next($request);
    }
}
