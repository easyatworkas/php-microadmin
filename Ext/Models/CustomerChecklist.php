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
 * @property mixed|null $id                             PRIMARY KEY             OK
 * @property mixed|null $customer_id                    FOREIGN KEY             OK
 * @property mixed|null $user_id                        FOREIGN KEY (optional)  OK
 * @property mixed|null $title
 * @property mixed|null $is_template
 * @property mixed|null $closed_at
 * @property mixed|null $completeness
 * @property mixed|null $deviations_count
 * @property mixed|null $deviations_completed_count
 * @property mixed|null $children                       array                   OK
 * @property mixed|null $categories                     array                   OK
 * @property mixed|null $items                          array                   OK
 */
class CustomerChecklist extends Model
{
    use HasChecklistItems;
    use HasChecklistCategories;

    protected $path = '/customers/{customer}/checklists';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function owner(): Customer
    {
        return Customer::get($this->customer_id);
    }

    /**
     * Gets User Model from optional property $user_id
     *
     * @return \Ext\Models\User|null
     * @author Torbjørn Kallstad
     */
    public function user(): ?User
    {
        return is_null($this->user_id) ? null : User::get($this->user_id);
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
