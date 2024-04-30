<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY             OK
 * @property mixed|null $customer_id            FOREIGN KEY             OK
 * @property mixed|null $name
 * @property mixed|null $type
 * @property mixed|null $items_count
 */
class CustomerTodoStatus extends Model
{
    protected $path = '/customers/{customer}/todo_statuses';

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
}
