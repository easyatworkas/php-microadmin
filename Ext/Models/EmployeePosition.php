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
class EmployeePosition extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/positions';

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
     * Gets Employee Model from property $pivot['employee_id']
     *
     * @return \Ext\Models\Employee
     * @author Torbjørn Kallstad
     */
    public function employee(): Employee
    {
        return $this->customer()->getEmployee($this->pivot['employee_id']);
    }

    /**
     * Gets CustomerPosition Model from property $pivot['position_id']
     *
     * @return \Ext\Models\CustomerPosition
     * @author Torbjørn Kallstad
     */
    public function position(): CustomerPosition
    {
        return $this->customer()->getPosition($this->pivot['position_id']);
    }
}
