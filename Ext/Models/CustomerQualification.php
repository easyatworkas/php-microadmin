<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasQualifiedEmployees;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $customer_id            FOREIGN KEY     OK
 * @property mixed|null $name
 */
class CustomerQualification extends Model
{
    use HasQualifiedEmployees;

    protected $path = '/customers/{customer}/qualifications';

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
