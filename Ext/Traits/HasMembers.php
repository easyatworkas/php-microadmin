<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\CustomerCustomerGroup;
use Ext\Models\CustomerGroup;
use Ext\Models\CustomerUserGroup;
use Ext\Models\Model;
use Ext\Models\SettingGroup;
use Ext\Models\User;
use Ext\Models\UserGroup;

trait HasMembers
{
    /**
     * @throws Exception
     */
    public function getMemberPath(): string
    {
        return match (getClass($this)) {
            CustomerCustomerGroup::class => '/customer_groups/' . $this->getKey() . '/members',
            CustomerUserGroup::class => '/user_groups/' . $this->getKey() . '/members',
            SettingGroup::class, CustomerGroup::class, UserGroup::class => $this->getFullPath() . '/members',
            default => throw new Exception('Unknown member path for class: ' . getClass($this)),
        };
    }

    /**
     * @throws Exception
     */
    public function getMemberClass(): string
    {
        return match ($this->model) {
            'customer' => Customer::class,
            'user' => User::class,
            'customer_group' => CustomerGroup::class,
            'user_group' => UserGroup::class,
            'setting_group' => SettingGroup::class,
            default => throw new Exception('Unknown member class for model: ' . $this->model),
        };
    }

    /**
     * @throws Exception
     */
    public function members(bool $inactive = true): array
    {
        return match ($this->model) {
            'customer' => static::newQuery($this->getMemberPath())
                ->setModel(Customer::class)
                ->includeInactive($inactive)
                ->getAll()
                ->all(),
            'user' => static::newQuery($this->getMemberPath())
                ->setModel(User::class)
                ->displayInactiveUsers($inactive)
                ->getAll()
                ->all(),
            default => throw new Exception('Can not get members of type ' . $this->model),
        };
    }

    public function activeMembers(): array
    {
        return $this->members(false);
    }

    public function getMember(string $key): ?Model
    {
        $member = null;
        try {
            $member = static::newQuery($this->getMemberPath() . "/$key")
                ->setModel($this->getMemberClass())
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $member;
    }

    public function findMember(array $attributes, ?string $when = 'now'): ?Model
    {
        foreach ($this->members() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model['pivot']['from'], $model['pivot']['to'])) return $model;
            }
        }
        return null;
    }

    public function findMembers(array $attributes = [], ?string $when = 'now'): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($when)) {
            return $this->members();
        }

        $found = [];

        foreach ($this->members() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model['pivot']['from'], $model['pivot']['to'])) $found[] = $model;
            }
        }
        return $found;
    }

    public function hasMember(int $memberId, ?string $when = 'now'): bool
    {
        return (bool)$this->findMember([ 'id' => $memberId ], $when);
    }

    public function hasMemberWith(array $attributes, ?string $when = 'now'): bool
    {
        return (bool)$this->findMember($attributes, $when);
    }

    public function addMember(int $memberId, ?string $from = 'now', $to = null): bool
    {
        if (!$this->hasMember($memberId, is_null($from) ? 'now' : $from))
        {
            eaw()->create($this->getMemberPath(), null, array_filter([
                'member_id' => $memberId,
                'from' => $from,
                'to' => $to,
            ], static function($var) {return $var !== null;} ));
            return true;
        }
        return false;
    }

    public function updateMembership(int $memberId, ?string $from = null, ?string $to = null): bool
    {
        // Return if both $from and $to are null
        if (is_null($from) && is_null($to)) return false;

//        // Get the membership
//        $membership = $this->getMember($memberId);
//
//        // If membership exists, update it
//        if ($membership) {

            try {
                eaw()->update($this->getMemberPath() . '/' . $memberId, [
                    'from' => $from,
                    'to' => $to,
                ]);
                return true;
            } catch (Exception $e) {
                // TODO: Log error
                echo $e->getMessage();
            }
//        }

        return false;
    }

    public function removeMember(int $memberId, ?string $from = null): bool
    {
        $endDate = is_null($from) ? 'now' : $from;

        if ($this->hasMember($memberId, $endDate))
        {
            try {
                $membership = $this->getMember($memberId);
                $to = $membership['pivot']['to'] ?? null;

                // Only update To if To is not already set, or if To is after $from
                if (is_null($to) || !is_null($from) && date_create($to) > date_create($from)) {
                    eaw()->update($this->getMemberPath() . '/' . $memberId, ['to' => toNowOrFuture($endDate)]);
                    return true;
                } else {
                    return false;
                }
            } catch (Exception $e) {
                logg()->info('{lred}'.$e->getCode().': '.$e->getMessage());
                return false;
            }
        }
        return false;
    }
}