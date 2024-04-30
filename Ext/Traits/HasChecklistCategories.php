<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\ChecklistCategory;

trait HasChecklistCategories
{
    public function categories(): array
    {
        return $this->client->query($this->getFullPath() . '/categories')
            ->setModel(ChecklistCategory::class)
            ->getAll()
            ->all();
    }

    public function getCategory(string $key): ?ChecklistCategory
    {
        foreach ($this->categories() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findCategory(array $attributes): ?ChecklistCategory
    {
        foreach ($this->categories() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findCategories(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->categories();
        }

        $found = [];

        foreach ($this->categories() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasCategory(array $attributes): bool
    {
        return (bool)$this->findCategory($attributes);
    }

    public function copyCategory(ChecklistCategory $source, ?int $parentId = null): ?ChecklistCategory
    {
        try {
            // Create new Instance
            $model = ChecklistCategory::newInstance(array_filter([
                'parent_id' => $parentId,
                'title' => $source->title,
                'weight' => $source->weight,
            ], static function ($var) {
                return $var !== null;
            }))->setPath('/customers/' . $this->getIdOf('customer') . '/checklists/' . $this->getIdOf('checklist') .'/categories');

            // Save
            $model->save();

            // Copy Root Items
            foreach ($source->items() as $item) {
                $model->copyItem($item, $model->id);
            }

            // Copy Categories
            foreach ($source->categories() as $category) {
                $model->copyCategory($category, $model->id);
            }

            // Return
            return $model;
        } catch (\Exception $e) {
            // TODO: Log error
//            logg()->info("{dred}Error copying Checklist Category $source->id: ".$e->getMessage()."\n");
            return null;
        }
    }
}