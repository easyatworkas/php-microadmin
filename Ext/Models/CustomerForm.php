<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY
 * @property mixed|null $customer_id    FOREIGN KEY
 * @property mixed|null $name
 * @property mixed|null $user_group_id  FOREIGN KEY (optional)
 * @property bool       $active
 */
class CustomerForm extends Model
{
    protected $path = '/customers/{customer}/forms';

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
     * Gets CustomerUserGroup Model from optional property $user_group_id
     *
     * @return \Ext\Models\CustomerUserGroup|null
     * @author Torbjørn Kallstad
     */
    public function userGroup(): ?CustomerUserGroup
    {
        return is_null($this->user_group_id) ? null : $this->owner()->getUserGroup($this->user_group_id);
    }
}
