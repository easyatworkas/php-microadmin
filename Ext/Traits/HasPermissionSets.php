<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerUserGroup;
use Ext\Models\PermissionSet;

trait HasPermissionSets
{
    private function getPermissionSetPath(): string
    {
        switch (getClass($this)) {
            case PermissionSet::class:
                return '/permission_sets/' . $this->getKey() . '/permission_sets';
            case CustomerUserGroup::class:
                return '/user_groups/' . $this->getKey() . '/permission_sets';
            default:
                return $this->getFullPath() . '/permission_sets';
        }
    }

    public function permissionSets(): array
    {
        return $this->client->query($this->getPermissionSetPath())
                ->setModel(PermissionSet::class)
                ->getAll()
                ->all();
    }

    public function getPermissionSet(string $key): ?PermissionSet
    {
        foreach ($this->permissionSets() as $model) {
            if ($model->id == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findPermissionSet(array $parameters): ?PermissionSet
    {
        foreach ($this->permissionSets() as $model) {
            if ($model->matches($parameters)) {
                return $model;
            }
        }
        return null;
    }

    public function findPermissionSets(array $parameters): array
    {
        $foundPermissionSets = [];

        foreach ($this->permissionSets() as $model) {
            if ($model->matches($parameters)) {
                $foundPermissionSets[] = $model;
            }
        }
        return $foundPermissionSets;
    }

    public function hasPermissionSet(array $attributes): bool
    {
        return (bool)$this->findPermissionSet($attributes);
    }

    public function addPermissionSet(int $setId): ?PermissionSet
    {
        if (!$this->hasPermissionSet([ 'id' => $setId ])) {
            try {
                eaw()->create($this->getPermissionSetPath(), null, [
                    'set_id' => $setId
                ]);

                return $this->getPermissionSet($setId);

            } catch (Exception $e) {
                echo $e->getMessage();
                return null;
            }

        }
        return null;
    }

    public function copyPermissionSet(PermissionSet $source): ?PermissionSet
    {
        return $this->addPermissionSet($source->id);
    }

    public function removePermissionSet(int $setId): bool
    {
        if ($this->hasPermissionSet(['id' => $setId])) {
            eaw()->delete($this->getPermissionSetPath() . '/' . $setId);

            return true;
        }
        return false;
    }

    // TODO: Find out where the set is used: Users, UserGroups, PermissionSets, LeaderRoles
    public function isUsedAt(): array
    {
        return [];
    }
}
