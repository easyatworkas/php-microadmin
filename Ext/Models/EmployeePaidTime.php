<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY             OK
 * @property mixed|null $employee_id        FOREIGN KEY             OK
 * @property mixed|null $customer_id        FOREIGN KEY             OK
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $business_date
 * @property mixed|null $business_unit_id   FOREIGN KEY (optional)  OK
 */
class EmployeePaidTime extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/paid_times';

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

    /**
     * Gets BusinessUnit Model from optional property $business_unit_id
     *
     * @return \Ext\Models\CustomerBusinessUnit|null
     * @author Torbjørn Kallstad
     */
    public function businessUnit(): ?CustomerBusinessUnit
    {
        return is_null($this->business_unit_id) ? null : $this->customer()->getBusinessUnit($this->business_unit_id);
    }
}
