<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasChecklistCategories;
use Ext\Traits\HasChecklistItems;

/**
 * @property mixed|null $id                     PRIMARY KEY             OK
 * @property mixed|null $parent_id              KEY                     OK
 * @property mixed|null $parent_type            used with parent_id     OK
 * @property mixed|null $title
 * @property mixed|null $weight
 * @property mixed|null $children               array                   OK
 * @property mixed|null $categories             array                   OK
 * @property mixed|null $items                  array                   OK
 */
class ChecklistCategory extends Model
{
    use HasChecklistItems;
    use HasChecklistCategories;

    protected $path = '/customers/{customer}/checklists/{checklist}/categories';

    /**
     * Gets Parent Model from property $parent_id based on $parent_type
     *
     * @return \Ext\Models\Model
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function parent(): Model
    {
        return match ($this->parent_type) {
            'checklist' => Customer::get($this->getIdOf('customer'))->getChecklist($this->parent_id),
            'checklists_category' => ChecklistCategory::get($this->parent_id),
            default => throw new \Exception("Not implemented for parent of type $this->parent_type"),
        };
    }

    /**
     * Gets list of all ChecklistItems and ChecklistCategories
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function children(): array
    {
        return array_merge($this->items(), $this->categories());
    }

    /**
     * Gets list of all ChecklistItem Models
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function items(): array
    {
        if (!is_array($this->getAttribute('items'))) {
            $withItems = static::newQuery($this->getPath())
                ->with([ 'items' ])
                ->get($this->getKey());

            $this->attributes[ 'items' ] = $withItems->getAttribute('items') ?? [];
        }

        return array_map(function (array $attributes) {
            return ChecklistItem::newInstance($attributes);
        }, $this->getAttribute('items'));
    }

    /**
     * Gets list of all ChecklistCategory Models
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function categories(): array
    {
        if (!is_array($this->getAttribute('categories'))) {
            $withCategories = static::newQuery($this->getPath())
                ->with([ 'categories' ])
                ->get($this->getKey());

            $this->attributes[ 'categories' ] = $withCategories->getAttribute('categories') ?? [];
        }

        return array_map(function (array $attributes) {
            return ChecklistCategory::newInstance($attributes);
        }, $this->getAttribute('categories'));
    }
}