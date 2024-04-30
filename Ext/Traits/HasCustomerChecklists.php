<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerChecklist;

trait HasCustomerChecklists
{
    public function checklists(): array
    {
        if (!$this->hasProduct("Checklists")) {
            return [];
        }

        return static::newQuery($this->getFullPath() . '/checklists')
            ->setModel(CustomerChecklist::class)
            ->template(false)
            ->getAll()
            ->all();
    }

    public function checklistTemplates(): array
    {
        if (!$this->hasProduct("Checklists")) {
            return [];
        }

        return static::newQuery($this->getFullPath() . '/checklists')
            ->setModel(CustomerChecklist::class)
            ->template(true)
            ->getAll()
            ->all();
    }

    public function allChecklists(): array
    {
        return array_merge($this->checklists(), $this->checklistTemplates());
    }

    public function getChecklist(string $key): ?CustomerChecklist
    {
        $checklist = null;
        try {
            $checklist = static::newQuery($this->getFullPath() . "/checklists/$key")
                ->setModel(CustomerChecklist::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $checklist;
    }

    public function findChecklist(array $attributes): ?CustomerChecklist
    {
        foreach ($this->allChecklists() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findChecklistWithItem(int $itemId): ?CustomerChecklist
    {
        foreach ($this->allChecklists() as $checklist) {
            if ($checklist->hasItem([ 'id' => $itemId ])) {
                return $checklist;
            }
        }
        return null;
    }

    public function findChecklistWithCategory(int $categoryId): ?CustomerChecklist
    {
        foreach ($this->allChecklists() as $checklist) {
            if ($checklist->hasCategory([ 'id' => $categoryId ])) {
                return $checklist;
            }
        }
        return null;
    }

    public function findChecklists(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->allChecklists();
        }

        $found = [];

        foreach ($this->allChecklists() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasChecklist(array $attributes): bool
    {
        return (bool)$this->findChecklist($attributes);
    }

    public function copyChecklist(CustomerChecklist $source): ?CustomerChecklist
    {
        // Only applies to Templates
        if (!$source->is_template) return null;

        try {
            // Create new Instance
            $template = CustomerChecklist::newInstance([
                'title' => $source->title,
                'is_template' => $source->is_template,
            ])->setPath("/customers/$this->id/checklists");

            // Save
            $template->save();

            // Copy Root Items
            foreach ($source->items() as $item) {
                $template->copyItem($item);
            }

            // Copy Categories
            foreach ($source->categories() as $category) {
                $template->copyCategory($category);
            }

            // Return
            return $template;
        } catch (\Exception $e) {
            // TODO: Log error
//            logg()->info("{dred}Error copying Checklist $source->id: ".$e->getMessage()."\n");
            return null;
        }
    }
}