<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerPostit;

trait HasCustomerPostits
{
    public function postits(): array
    {
        if (!$this->hasProduct('Postits')) return [];

        return static::newQuery($this->getFullPath().'/postits')
            ->setModel(CustomerPostit::class)
            ->getAll()
            ->all();
    }

    public function findPostit(array $attributes): ?CustomerPostit
    {
        foreach ($this->postits() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findPostits(array $attributes): array
    {
        $models = [];
        foreach ($this->postits() as $model) {
            if ($model->matches($attributes)) {
                $models[] = $model;
            }
        }
        return $models;
    }

    public function getPostit(int $key): ?CustomerPostit
    {
        $postit = null;
        try {
            $postit = static::newQuery($this->getFullPath() . "/postits/$key")
                ->setModel(CustomerPostit::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $postit;
    }
}