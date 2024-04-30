<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY     OK
 * @property mixed|null $customer_id    FOREIGN KEY     OK
 * @property mixed|null $name
 * @property mixed|null $pivot          array           OK
 */
class EmployeeQualification extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/qualifications';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function customer(): Customer
    {
        return Customer::get($this->customer_id);
    }

    /**
     * Gets Employee Model from property $pivot['qualified_id']
     *
     * @return \Ext\Models\Employee
     * @author Torbjørn Kallstad
     */
    public function employee(): Employee
    {
        return $this->customer()->getEmployee($this->pivot['qualified_id']);
    }

    /**
     * Gets CustomerQualification Model from property $pivot['qualification_id']
     *
     * @return \Ext\Models\CustomerQualification
     * @author Torbjørn Kallstad
     */
    public function qualification(): CustomerQualification
    {
        return $this->customer()->getQualification($this->pivot['qualification_id']);
    }
}
