<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY         OK
 * @property mixed|null $customer_id        FOREIGN KEY         OK
 * @property mixed|null $model
 * @property mixed|null $filter['type_id']  FOREIGN KEY         OK
 * @property mixed|null $provider
 * @property mixed|null $restricted
 */
class CustomerSignable extends Model
{
    protected $path = '/customers/{customer}/signables';

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
     * Gets CustomerFileType Model from property $filter['type_id']
     *
     * @return \Ext\Models\CustomerFileType
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function fileType(): CustomerFileType
    {
        return $this->owner()->getFileType($this->filter['type_id']);
    }
}
