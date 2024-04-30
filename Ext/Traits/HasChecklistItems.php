<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\ChecklistItem;

trait HasChecklistItems
{
    public function items(): array
    {
        return $this->client->query($this->getFullPath() . '/items')
            ->setModel(ChecklistItem::class)
            ->getAll()
            ->all();
    }

    public function getItem(string $key): ?ChecklistItem
    {
        foreach ($this->items() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findItem(array $attributes): ?ChecklistItem
    {
        foreach ($this->items() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findItems(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->items();
        }

        $found = [];

        foreach ($this->items() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasItem(array $attributes): bool
    {
        return (bool)$this->findItem($attributes);
    }

    public function copyItem(ChecklistItem $source, ?int $parentId = null): ?ChecklistItem
    {
        try {
            // Create new Instance
            $item = ChecklistItem::newInstance(array_filter([
                'parent_id' => $parentId,
                'title' => $source->title,
                'description' => $source->description,
                'type' => $source->type,
                'data' => $source->data,
                'weight' => $source->weight,
                'options' => $source->options,
            ], static function ($var) {
                return $var !== null;
            }))->setPath('/customers/' . $this->getIdOf('customer') . '/checklists/' . $this->getIdOf('checklist') . '/items');

            // Save
            $item->save();

            // Return
            return $item;
        } catch (\Exception $e) {
            // TODO: Log error
//            logg()->info("{dred}Error copying Checklist Item $source->id: ".$e->getMessage()."\n");
            return null;
        }
    }
}