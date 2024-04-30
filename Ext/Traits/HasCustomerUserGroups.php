<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerUserGroup;

trait HasCustomerUserGroups
{
    public function userGroups(): array
    {
        return static::newQuery($this->getFullPath() . '/user_groups')
            ->setModel(CustomerUserGroup::class)
            ->getAll()
            ->all();
    }

    public function getUserGroup(string $key): ?CustomerUserGroup
    {
        $userGroup = null;
        try {
            $userGroup = static::newQuery($this->getFullPath() . "/user_groups/$key")
                ->setModel(CustomerUserGroup::class)
                ->get()
                ->setPath($this->getFullPath() . '/user_groups');
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $userGroup;
    }

    public function findUserGroup(array $attributes): ?CustomerUserGroup
    {
        foreach ($this->userGroups() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findUserGroups(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->userGroups();
        }

        $found = [];

        foreach ($this->userGroups() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasUserGroup(array $attributes): bool
    {
        return (bool)$this->findUserGroup($attributes);
    }

    public function addUserGroup(string $name, bool $checkExisting = true): ?CustomerUserGroup
    {
        if (!$checkExisting || !$this->hasUserGroup(['name' => $name])) {
            $userGroup = CustomerUserGroup::newInstance([
                'name' => $name,
            ])->setPath("/customers/$this->id/user_groups");

            $userGroup->save();

            return $userGroup;
        }
        return null;
    }

    public function copyUserGroup(CustomerUserGroup $source, bool $checkExisting = true, bool $addPermissions = true, bool $addResponsible = false, bool $addMembers = false): ?CustomerUserGroup
    {
        $userGroup = $this->addUserGroup($source->name, $checkExisting);

        if (is_null($userGroup)) {
            return null;
        }

        if ($addPermissions) {
            foreach ($source->permissions() as $permission) {
                $userGroup->copyPermission($permission);
            }

            foreach ($source->permissionSets() as $permissionSet) {
                $userGroup->copyPermissionSet($permissionSet);
            }
        }

        if ($addResponsible) {
            // TODO: fix
        }

        if ($addMembers) {
            foreach ($source->members() as $member) {
                if ($this->hasUserWith(['id' => $member->id])) {
                    $userGroup->addMember($member->id);
                }
            }
        }

        return $userGroup;
    }

    public function deleteUserGroups(array $groups): bool
    {
        $success = true;
        foreach ($groups as $group) {
            $success = $group->delete() && $success;
        }

        return $success;
    }
}