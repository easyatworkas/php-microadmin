<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasCustomerAccess;
use Ext\Traits\HasGroupMemberships;
use Ext\Traits\HasProperties;
use Ext\Traits\HasPermissions;
use Ext\Traits\HasPermissionSets;

/**
 * @property mixed|null $id                         PRIMARY KEY     OK
 * @property mixed|null $first_name
 * @property mixed|null $last_name
 * @property mixed|null $email
 * @property mixed|null $country_code
 * @property mixed|null $phone
 * @property mixed|null $language_code
 * @property mixed|null $profile_picture_id
 * @property mixed|null $last_active
 * @property mixed|null $warnings
 * @property mixed|null $name
 */
class User extends Model
{
    use HasCustomerAccess;
    use HasGroupMemberships;
    use HasProperties;
    use HasPermissions;
    use HasPermissionSets;

    protected $path = '/users';

    public function userGroups(int $customerId = null): array
    {
        $allUserGroups = $this->client->query($this->getFullPath() . '/groups')
            ->setModel(UserGroup::class)
            ->includeInactive(true)
            ->getAll()
            ->all();

        if (is_null($customerId)) return $allUserGroups;

        $filtered = [];
        foreach ($allUserGroups as $userGroup)
        {
            if ($userGroup->owner_type == 'customer' &&
                $userGroup->owner_id == $customerId)
            {
                $filtered[] = $userGroup;
            }
        }
        return $filtered;
    }

//    public function isMemberOf(int $userGroupId, string $atPointInTime = 'now'): bool
//    {
//        $userGroups = $this->userGroups()->getAll();
//        foreach ($userGroups as $userGroup) {
//            if ($userGroup->id == $userGroupId) {
//                if (dateTimeWithin($atPointInTime, $userGroup['pivot']['from'], $userGroup['pivot']['to'])) return true;
//            }
//        }
//        return false;
//    }

    public function isEmployedAt(int $customerId, string $atPointInTime = 'now'): bool
    {
        $customer = Customer::get($customerId);
        $employed = $customer->users()->employed(true)->displayInactiveUsers(true)->getAll();
        foreach ($employed as $user) {
            if ($user->id == $this->id) {
                if (dateTimeWithin($atPointInTime, $user['pivot']['from'], $user['pivot']['to'])) return true;
            }
        }
        return false;
    }

    // Getters ---------------------------------------------------------------------------------------------------------
    public function getUserGroup(string $search, string $property = 'id'): ?UserGroup
    {
        $groups = $this->userGroups()->getAll();
        foreach ($groups as $group)
        {
            if ($group->{$property} == $search)
            {
                return $group;
            }
        }
        return null;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function employments(): array
    {
        $employments = [];

        foreach ($this->accesses() as $access) {
            $customer = Customer::get($access->customer_id);
            $employee = $customer->findEmployee(['user_id' => $this->id]);
            if ($employee) {
                $employments[$customer->id]['customer'] = $customer;
                $employments[$customer->id]['employee'] = $employee;
            }
        }

        return $employments;
    }

    // Example: User::get(12)->isAllowed('customers.3.timepunches.*.get')
    public function isAllowed(string $permission): bool
    {
        return eaw()->create($this->getFullPath() . '/is_allowed_many', [
            'mode' => 'isAllowed',
            'permissions' => [$permission],
        ])[$permission];
    }

    // Example: User::get(12)->permissionChildren('customers', 'get', true)
    public function permissionChildren(string $prefix, string $filter, bool $filterValue = true): array
    {
        return eaw()->create($this->getFullPath() . '/is_allowed_many', [
            'mode' => 'permissionChildren',
            'permissions' => [$prefix],
            'filter' => $filter,
            'filterValue' => $filterValue,
        ]);
    }

    // Example: User::get(12)->canRead('customers.3.employees', 'employee', 5, 'first_name')
    public function canRead(string $prefix, string $model, int $id, string $attribute, int $stack = 1, string $suffix = 'read'): bool
    {
        // Try getting the Customer's Stack ID
        $customerStackId = $stack;
        try {
            $splitPrefix = explode('.', $prefix);

            for ($i = 0; $i <= count($splitPrefix) - 1; $i++)
            {
                if (substr($splitPrefix[$i], 0, mb_strlen('customers')) == 'customers') {
                    $customerStackId = Customer::get($splitPrefix[$i + 1])->stack_id;
                }
            }
        } catch (\Exception $e) {
            // Do nothing
        }
        return in_array($attribute, eaw()->create($this->getFullPath() . '/is_allowed_many', [
            'stack' => $customerStackId,
            'mode' => 'canRead',
            'models' => [
                [
                    'prefix' => $prefix,
                    'type' => $model,
                    'id' => $id,
                    'suffix' => $suffix,
                ]
            ],
        ])[0]['attributes']);
    }

    // Example: User::get(12)->canWrite('customers.3.employees', 'employee', 5, 'first_name')
    public function canWrite(string $prefix, string $model, int $id, string $attribute, int $stack = 1): bool
    {
        return $this->canRead($prefix, $model, $id, $attribute, $stack, 'write');
    }
}
