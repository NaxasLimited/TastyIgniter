<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Services;

use Igniter\User\Classes\PermissionManager;
use Igniter\User\Models\UserRole;
use Naxas\RestaurantOps\Contracts\AuditLogger;
use Naxas\RestaurantOps\Support\RoleProfiles;

final class RoleSynchronizer
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PermissionManager $permissionManager,
    ) {}

    private const LEGACY_MANAGER_DENIED_PERMISSIONS = [
        'Admin.Locations',
        'Admin.Payments',
        'Admin.Staffs',
        'Admin.StaffGroups',
        'Admin.MediaManager',
        'Site.Themes',
        'System.Settings',
        'System.Extensions',
    ];

    public function sync(bool $dryRun = true, bool $createMissing = false, bool $addMissingPermissions = false, ?string $only = null, bool $hardenLegacyManager = false): array
    {
        $result = ['detected' => [], 'created' => [], 'updated' => [], 'conflicts' => [], 'missing permissions' => [], 'skipped' => [], 'optional unavailable' => []];
        $profiles = RoleProfiles::all();
        $registered = collect($this->permissionManager->listPermissions())->pluck('code')->all();
        $referenced = collect($profiles)->pluck('permissions')->flatten()->unique()->all();
        $result['missing permissions'] = array_values(array_diff($referenced, $registered));
        $optional = collect($profiles)->pluck('optional_permissions')->flatten()->filter()->unique()->all();
        $result['optional unavailable'] = array_values(array_diff($optional, $registered));

        if ($result['missing permissions']) {
            $this->audit->info('restaurant_ops.role_sync_blocked', ['missing_permissions' => $result['missing permissions']]);

            return $result;
        }

        foreach ($profiles as $profile => $definition) {
            if ($only && ! in_array($only, [$profile, $definition['code']], true)) {
                continue;
            }

            $result['detected'][] = $definition['code'];
            $profilePermissions = array_values(array_unique(array_merge(
                $definition['permissions'],
                array_values(array_intersect($definition['optional_permissions'] ?? [], $registered)),
            )));
            $role = UserRole::query()->where('code', $definition['code'])->first();
            if (! $role) {
                if (! $createMissing || $dryRun) {
                    $result['skipped'][] = $definition['code'].' (missing)';

                    continue;
                }

                $role = UserRole::query()->create([
                    'name' => $definition['name'], 'code' => $definition['code'],
                    'description' => 'Standard Restaurant Operations role. Permissions may be customized.',
                    'permissions' => array_fill_keys($profilePermissions, 1),
                ]);
                $result['created'][] = $definition['code'];
                $this->audit->info('restaurant_ops.role_created', ['role_id' => $role->getKey(), 'profile' => $profile]);

                continue;
            }

            $missing = array_diff($definition['permissions'], array_keys((array) $role->permissions));
            if (! $missing) {
                continue;
            }

            if ((! $addMissingPermissions && ! $createMissing) || $dryRun) {
                $result['skipped'][] = $definition['code'].' ('.count($missing).' permissions missing)';

                continue;
            }

            $role->permissions = array_replace((array) $role->permissions, array_fill_keys($missing, 1));
            $role->save();
            $result['updated'][] = $definition['code'].' (+'.count($missing).')';
            $this->audit->info('restaurant_ops.role_permissions_added', ['role_id' => $role->getKey(), 'count' => count($missing)]);
        }

        if ($hardenLegacyManager && (!$only || in_array($only, ['manager', 'legacy_manager'], true))) {
            $this->hardenLegacyManager($profiles['branch_manager'], $registered, $dryRun, $result);
        }

        $this->audit->info($dryRun ? 'restaurant_ops.role_sync_preview' : 'restaurant_ops.role_sync', array_map('count', $result));

        return $result;
    }

    private function hardenLegacyManager(array $branchManager, array $registered, bool $dryRun, array &$result): void
    {
        $role = UserRole::query()->where('code', 'manager')->first();
        if (! $role) {
            $result['skipped'][] = 'manager (missing)';

            return;
        }

        $current = (array)$role->permissions;
        $allowedAdditions = array_values(array_unique(array_merge(
            $branchManager['permissions'],
            array_values(array_intersect($branchManager['optional_permissions'] ?? [], $registered)),
        )));
        $next = array_replace($current, array_fill_keys($allowedAdditions, 1));
        foreach (self::LEGACY_MANAGER_DENIED_PERMISSIONS as $permission) {
            unset($next[$permission]);
        }

        $removed = array_values(array_intersect(array_keys($current), self::LEGACY_MANAGER_DENIED_PERMISSIONS));
        $added = array_values(array_diff($allowedAdditions, array_keys($current)));
        if (!$removed && !$added) {
            return;
        }

        if ($dryRun) {
            $result['skipped'][] = 'manager (legacy hardening: -'.count($removed).', +'.count($added).')';

            return;
        }

        $role->permissions = $next;
        $role->save();
        $result['updated'][] = 'manager (legacy hardening: -'.count($removed).', +'.count($added).')';
        $this->audit->info('restaurant_ops.legacy_manager_hardened', ['role_id' => $role->getKey(), 'removed' => $removed, 'added_count' => count($added)]);
    }
}
