<?php

namespace App\Core\Http\Middleware;

use App\Core\Application\Module\ModuleGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to any route that belongs to a disabled module.
 * A disabled module is blocked at the backend — not merely hidden from the menu.
 *
 * Usage in routes: ->middleware('module:module-slug')
 */
class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleGate $moduleGate) {}

    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        if (!$this->moduleGate->isEnabled($moduleSlug)) {
            return response()->json([
                'message' => "Module '{$moduleSlug}' is not enabled for this tenant.",
                'code' => 'MODULE_DISABLED',
            ], 403);
        }

        return $next($request);
    }
}
