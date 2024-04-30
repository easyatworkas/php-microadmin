<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\Customer;
use Ext\Models\CustomerAccess;

trait HasCustomerAccess
{
    public function accesses(): array
    {
        $accesses = [];

        $customers = $this->client->query($this->getFullPath() . '/customers')
            ->setModel(Customer::class)
            ->getAll()
            ->all();

        foreach ($customers as $customer)
        {
            $accesses[] = CustomerAccess::newInstance($customer['pivot'])
                ->setPath('/customers/' . $customer->id . '/users/' . $this->id . '/access');
        }

        return $accesses;
    }

    public function getCustomerAccess(string $key): ?CustomerAccess
    {
        return $this->findCustomerAccess(['id' => $key]);
    }

    public function findCustomerAccess(array $attributes, string $when = 'now'): ?CustomerAccess
    {
        foreach ($this->accesses() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) return $model;
            }
        }
        return null;
    }

    public function findCustomerAccesses(array $attributes, string $when = 'now'): array
    {
        $models = [];
        foreach ($this->accesses() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) $models[] = $model;
            }
        }
        return $models;
    }

    public function hasAccessTo(int $customerId, string $when = 'now'): bool
    {
        return (bool)($this->findCustomerAccess(['customer_id' => $customerId], $when));
    }
}