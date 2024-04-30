<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete: TODO: Test and verify
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY         OK
 * @property mixed|null $original_id        KEY
 * @property mixed|null $mirror_id          KEY? (same as ID of Customer)
 * @property mixed|null $parent_id          KEY (optional)
 */
class CustomerKpiMirror extends Model
{
    /**
     * Gets Customer Model from property $original_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function original(): Customer
    {
        return Customer::get($this->customer_id);
    }

    /**
     * Gets Customer Model from optional property $parent_id
     *
     * @return \Ext\Models\Customer|null
     * @author Torbjørn Kallstad
     */
    public function parent(): ?Customer
    {
        return is_null($this->parent_id) ? null : Customer::get($this->parent_id);
    }

    /**
     * Deletes the CustomerKpiMirror
     * Overrides the delete() method in php-eaw-client
     *
     * @author Torbjørn Kallstad
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $this->client->delete(rtrim($this->getPath(), 'from to') . $this->id);

        return true;
    }
}
