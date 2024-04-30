<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\OauthClient;
use Ext\Models\Customer;
use Ext\Models\CustomerUserGroup;
use Ext\Models\Permission;
use Ext\Models\PermissionSet;
use Ext\Models\User;
use Ext\Models\UserGroup;

trait HasPermissions
{
    function flushPermissions(bool $deep = false): bool
    {
        if ($deep && (getClass($this) == \Ext\Models\User::class)) {
            foreach ($this->groupMemberships() as $membership) {
                $group = UserGroup::get($membership->group_id);

                foreach ($group->permissionSets() as $set) {
                    $set->flushPermissions();
                }
                $group->flushPermissions();
            }
        } else {
            switch (getClass($this)) {
                case \Ext\Models\User::class:
                    eaw()->create($this->getFullPath() . '/flush_permissions');
                    break;

                case \Ext\Models\UserGroup::class:
                case \Ext\Models\CustomerUserGroup::class:
                    eaw()->create('/user_groups/' . $this->getKey() . '/flush_permissions');
                    break;

                case \Ext\Models\PermissionSet::class:
                    eaw()->create('/permission_sets/' . $this->getKey() . '/flush');
                    break;

                default:
                    throw new \Exception('Can not flush permissions for model ' . getClass($this));
            }
        }

        return true;
    }

    function getPermissionPath(): string
    {
        return match (getClass($this)) {
            \Ext\Models\User::class => '/users/' . $this->getKey() . '/permissions',
            \Ext\Models\OauthClient::class => '/clients/' . $this->getKey() . '/permissions',
            \Ext\Models\PermissionSet::class => '/permission_sets/' . $this->getKey() . '/permissions',
            \Ext\Models\CustomerUserGroup::class => '/user_groups/' . $this->getKey() . '/permissions',
            default => $this->getFullPath() . '/permissions',
        };
    }

    public function permissions(): ?array
    {
        $permissions = $this->client->query($this->getPermissionPath())
            ->setModel(Permission::class)
            ->getAll()
            ->all();

        foreach ($permissions as $permission) {
            $permission->setOwner($this);
        }

        return $permissions;
    }

    public function getPermission(string $node): ?Permission
    {
        foreach ($this->permissions() as $permission) {
            if ($permission->node == $node) return $permission;
        }
        return null;
    }

    public function findPermission(array $parameters): ?Permission
    {
        foreach ($this->permissions() as $permission) {
            if ($permission->matches($parameters)) return $permission;
        }
        return null;
    }

    public function findPermissions(array $parameters): array
    {
        $foundPermissions = [];

        foreach ($this->permissions() as $permission) {
            if ($permission->matches($parameters)) {
                $foundPermissions[] = $permission;
            }
        }
        return $foundPermissions;
    }

    public function hasPermission(array $attributes): bool
    {
        return (bool)$this->findPermission($attributes);
    }

    public function addPermission(string $node, bool $value, bool $verbose = false): ?Permission
    {
        if (!$this->hasPermission([ 'node' => $node ]))
        {
            $newPermission = Permission::newInstance([
                'node' => $node,
                'value' => $value,
            ])->setPath($this->getPermissionPath());

            try {
                if ($newPermission->save()) {
                    if ($verbose) logg()->info("{dgreen}.");
                } else {
                    if ($verbose) logg()->info("{dyellow}.");
                }
            } catch (\Exception $e) {
                if ($verbose) logg()->info("{dred}!");
            }

            return $newPermission;
        }
        return null;
    }

    public function updatePermission(string $node, bool $newValue): bool
    {
        try {
            eaw()->update($this->getPermissionPath() . "/$node", [ 'value' => $newValue ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function copyPermission(Permission $source, bool $handleDynamic = true): ?Permission
    {
        $node = $source->node;
        $value = $source->value;

        if ($handleDynamic)
        {
            // Handle Dynamic Node
            $dynamicNode = $source->getDynamicNode();
            if ($dynamicNode) {

                $parts = explode('.', trim($dynamicNode[0], '.'));
                $newId = '?';

                switch ($parts[0]) {
                    case 'user_groups':
                        try {
                            // Get original UserGroup
                            $original = UserGroup::get($parts[1]);

                            if ($original) {
                                // Look for UserGroup with same name
                                $newId = $this->owner()->findUserGroup([ 'name' => $original->name ])->id ?? '?';
                            }
                        } catch (\Exception $e) {
                            // TODO: Log error
                        }

                        // Replace original id with new
                        $node = str_replace($parts[1], $newId, $node);

                        break;
                    case 'reports':
                        try {
                            // Get owner (Customer)
                            $owner = $source->getOwner();
                            $customerId = match (getClass($owner)) {
                                \Ext\Models\CustomerUserGroup::class => $owner->owner_id,
                                \Ext\Models\PermissionSet::class => $dynamicNode[1],
                                default => null,
                            };

                            // Get original Report
                            $original = Customer::get($customerId)->getReport($parts[1]);

                            if ($original) {
                                // Look for Report with same class
                                $newId = $this->owner()->findReport([ 'class' => $original->class ])->id ?? '?';
                            }
                        } catch (\Exception $e) {
                            // TODO: Log error
                        }

                        // Replace original id with new
                        $node = str_replace($parts[1], $newId, $node);

                        break;
                    case 'customers':
                        // Replace original id with placeholder
                        $node = str_replace($parts[1], '{customer}', $node);

                        break;
                    default:
                        throw new \Exception('Not implemented for [' . $parts[0] . ']');
                }
            }

            // Handle Dynamic Value
            $dynamicValue = $source->getDynamicValue();
            if ($dynamicValue) {
                // TODO: implement
            }
        }

        return $this->addPermission($node, $value);
    }
}
