<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerBillingItem;

trait HasCustomerBillingItems
{
    public function billingItems(): array
    {
        return $this->client->query($this->getFullPath().'/billing_items')
            ->setModel(CustomerBillingItem::class)
            ->getAll()
            ->all();
    }

    public function getBillingItem(string $key): ?CustomerBillingItem
    {
        foreach ($this->billingItems() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findBillingItem(array $attributes): ?CustomerBillingItem
    {
        foreach ($this->billingItems() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findBillingItems(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->billingItems();
        }

        $found = [];

        foreach ($this->billingItems() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasBillingItem(array $attributes): bool
    {
        return (bool)$this->findBillingItem($attributes);
    }
}