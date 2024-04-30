<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\User;
use Ext\Models\CustomerUser;

trait HasCustomerUsers
{
    public function users(): array
    {
        return $this->client->query($this->getFullPath() . '/users')
            ->setModel(User::class)
            ->displayInactiveUsers(true)
            ->getAll()
            ->all();
    }

    public function activeUsers(): array
    {
        return $this->client->query($this->getFullPath() . '/users')
            ->setModel(User::class)
            ->displayInactiveUsers(false)
            ->getAll()
            ->all();
    }

    public function employedUsers(): array
    {
        return $this->client->query($this->getFullPath() . '/users')
            ->setModel(User::class)
            ->displayInactiveUsers(true)
            ->employed(true)
            ->getAll()
            ->all();
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function customerUsers(): array
    {
        return $this->client->query($this->getFullPath() . '/users')
            ->setModel(CustomerUser::class)
            ->getAll()
            ->all();
    }

    public function findCustomerUser(array $attributes = []): ?CustomerUser
    {
        foreach ($this->customerUsers() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findCustomerUsers(array $attributes = [], string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->customerUsers();
        }

        $found = [];
        $start = is_null($from) ? $this->from : $from;
        $end = $to;

        foreach ($this->customerUsers() as $model) {
            if ($model->matches($attributes)) {
                if (intervalWithin($model->from, $model->to, $start, $end)) {
                    $found[] = $model;
                }
            }
        }
        return $found;
    }

//    public function deactivateUser(User $user, string $when = 'now'): bool
//    {
//        $access = $this->findCustomerUser([ 'user_id' => $user->id ]);
//        if ($access)
//        {
//            try {
//                $access->update([ 'to' => $when ]);
//
//                return true;
//            } catch (Exception $e) {
//                return false;
//            }
//        }
//        return false;
//    }

    // -----------------------------------------------------------------------------------------------------------------

//    public function hasUserWith(array $parameters, ?string $when = 'now'): ?User
//    {
//        foreach ($this->users() as $user) {
//            if ($user->matches($parameters)) {
//                if (dateTimeWithin($when, $user['pivot']['from'], $user['pivot']['to'])) return $user;
//            }
//        }
//        return null;
//    }
    public function addUserAccess(User $user, string $from = 'now', $to = null): bool
    {
        if (!$user->hasAccessTo($this->id, $from))
        {
            eaw()->create("/customers/$this->id/users/$user->id/access", null, array_filter([
                'from' => $from,
                'to' => $to
            ], static function($var) {return $var !== null;}));
            return true;
        }
        return false;
    }
}