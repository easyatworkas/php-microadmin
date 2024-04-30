<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 * [x] TODO: allGroupMemberships, activeGroupMemberships
 */

namespace Ext\Traits;

use Ext\Models\GroupMembership;
use Ext\Models\UserGroup;

trait HasGroupMemberships
{
    public function groupMemberships(): array
    {
        $groupMemberships = [];

        $groups = $this->client->query($this->getFullPath() . '/groups')
            ->setModel(UserGroup::class)
            ->getAll()
            ->all();

        foreach ($groups as $group)
        {
            $membership = GroupMembership::newInstance($group['pivot'])
                ->setPath('/customers/' . $group->owner_id . '/user_groups/' . $group->id . '/members');
            $membership->member_type = strtolower($this::class);
            $groupMemberships[] = $membership;
        }

        return $groupMemberships;
    }

    public function findGroupMembership(array $attributes, string $when = 'now'): ?GroupMembership
    {
        foreach ($this->groupMemberships() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) return $model;
            }
        }
        return null;
    }

    public function findGroupMemberships(array $attributes, string $when = 'now'): array
    {
        $models = [];
        foreach ($this->groupMemberships() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) $models[] = $model;
            }
        }
        return $models;
    }

    public function isMemberOf(int $groupId, string $when = 'now'): bool
    {
        return (bool)($this->findGroupMembership(['customer_id' => $groupId], $when));
    }
}