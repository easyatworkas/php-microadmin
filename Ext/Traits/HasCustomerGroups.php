<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\CustomerCustomerGroup;
use Ext\Models\CustomerGroup;

trait HasCustomerGroups
{
    private function customerGroupClass(): string
    {
        return match (getClass($this)) {
            Customer::class => CustomerCustomerGroup::class,
            default => CustomerGroup::class
        };
    }

    public function allCustomerGroups(): array
    {
        return static::newQuery($this->getFullPath() . '/customer_groups')
            ->setModel($this->customerGroupClass())
            ->includeInactive(true)
            ->getAll()
            ->all();
    }

    public function activeCustomerGroups(): array
    {
        return static::newQuery($this->getFullPath() . '/customer_groups')
            ->setModel($this->customerGroupClass())
            ->includeInactive(false)
            ->getAll()
            ->all();
    }

    public function getCustomerGroup(string $key): ?CustomerGroup
    {
        $customerGroup = null;
        try {
            $customerGroup = static::newQuery($this->getFullPath() . "/customer_groups/$key")
                ->setModel($this->customerGroupClass())
                ->includeInactive(true)
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $customerGroup;
    }

    public function findCustomerGroup(array $attributes, string $when = 'now'): ?CustomerGroup
    {
        foreach ($this->allCustomerGroups() as $customerGroup) {
            if ($customerGroup->matches($attributes)) {
                if (dateTimeWithin($when, $customerGroup['pivot']['from'], $customerGroup['pivot']['to'])) {
                    return $customerGroup;
                }
            }
        }
        return null;
    }

    public function findCustomerGroups(array $attributes, string $from = null, string $to = null, bool $strict = false): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->allCustomerGroups();
        }

        $foundCustomerGroups = [];
        $start = is_null($from) ? $this->created_at : $from;
        $end = $to;

        foreach ($this->allCustomerGroups() as $customerGroup) {
            if ($customerGroup->matches($attributes)) {
                if (intervalWithin($start, $end, $customerGroup['pivot']['from'], $customerGroup['pivot']['to'],
                    $strict)) {
                    $foundCustomerGroups[] = $customerGroup;
                }
            }
        }
        return $foundCustomerGroups;
    }

    public function hasCustomerGroup(array $attributes, string $when = 'now'): bool
    {
        return (bool)$this->findCustomerGroup($attributes, $when);
    }

    public function belongsToCustomerGroup(int $customerGroupId, string $when = 'now'): bool
    {
        try {
            $customerGroup = CustomerGroup::get($customerGroupId);

            return $customerGroup->hasMember($this->id, $when);
        } catch (Exception $e) {
            return false;
        }
    }

    public function addToCustomerGroup(int $customerGroupId, string $when = 'now'): bool
    {
        try {
            $customerGroup = CustomerGroup::get($customerGroupId);

            if (!$customerGroup->hasMember($this->id, $when)) {
                return $customerGroup->addMember($this->id, $when);
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeFromCustomerGroup(int $customerGroupId, string $when = 'now'): bool
    {
        try {
            $customerGroup = CustomerGroup::get($customerGroupId);

            if (!$customerGroup->hasMember($this->id, $when)) {
                return $customerGroup->removeMember($this->id, $when);
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}