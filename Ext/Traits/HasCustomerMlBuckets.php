<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerMlBucket;

trait HasCustomerMlBuckets
{
    public function mlBuckets(): array
    {
        if (!$this->hasProduct('Machine Learning')) return [];

        return $this->client->query($this->getFullPath() . '/ml_buckets')
            ->setModel(CustomerMlBucket::class)
            ->getAll()
            ->all();
    }

    public function getMlBucket(string $key): ?CustomerMlBucket
    {
        return $this->findMlBucket(['uuid' => $key], null);
    }

    public function findMlBucket(array $attributes): ?CustomerMlBucket
    {
        foreach ($this->mlBuckets() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findMlBuckets(array $attributes): array
    {
        $models = [];
        foreach ($this->mlBuckets() as $model) {
            if ($model->matches($attributes)) {
                $models[] = $model;
            }
        }
        return $models;
    }

    public function hasMlBucket(array $attributes): bool
    {
        return !is_null($this->findMlBucket($attributes));
    }
}