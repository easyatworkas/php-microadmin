<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY     OK
 * @property mixed|null $employee_id    FOREIGN KEY     OK
 * @property mixed|null $customer_id    FOREIGN KEY     to be implemented
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $vacation
 * @property mixed|null $name
 * @property mixed|null $availability   array
 */
class OffTime extends Model
{
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
