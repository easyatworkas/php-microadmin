<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                         PRIMARY KEY         OK
 * @property mixed|null $customer_id                FOREIGN KEY         OK
 * @property mixed|null $employee_id                FOREIGN KEY         OK
 * @property mixed|null $cost
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $number
 * @property mixed|null $customer                   array['name']
 */
class EmployeeExternalEmployee extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/external_employees';

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
     * Gets Employee Model from property $employee_id
     *
     * @return \Ext\Models\Employee
     * @author Torbjørn Kallstad
     */
    public function employee(): Employee
    {
        return $this->customer()->getEmployee($this->employee_id);
    }
}
