<?php

namespace App\Core\Application\Navigation;

use App\Core\Application\Auth\PermissionChecker;
use App\Core\Application\Module\ModuleGate;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Builds the navigation items visible to the current user.
 * Only items whose module is active AND the user has permission are included.
 */
class NavigationBuilder
{
    public function __construct(
        private readonly ModuleGate $moduleGate,
        private readonly PermissionChecker $permissionChecker
    ) {}

    /**
     * Return navigation items filtered by module availability and user permissions.
     *
     * $items format:
     * [
     *   ['label' => 'Dashboard', 'route' => 'dashboard', 'module' => null, 'permission' => null],
     *   ['label' => 'Clientes',  'route' => 'crm.clientes', 'module' => 'crm', 'permission' => 'crm.clientes.view'],
     * ]
     */
    public function build(Authenticatable $user, array $items): array
    {
        return array_values(array_filter($items, function (array $item) use ($user) {
            if (!empty($item['module']) && !$this->moduleGate->isEnabled($item['module'])) {
                return false;
            }

            if (!empty($item['permission']) && !$this->permissionChecker->check($user, $item['permission'])) {
                return false;
            }

            return true;
        }));
    }
}
