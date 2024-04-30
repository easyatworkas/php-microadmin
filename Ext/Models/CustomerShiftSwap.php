<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY                 OK
 * @property mixed|null $customer_id    FOREIGN KEY                 OK
 * @property mixed|null $from_id        FOREIGN KEY (employee)      OK
 * @property mixed|null $to_id          FOREIGN KEY (employee)      OK
 * @property mixed|null $shift_id       FOREIGN KEY                 OK
 * @property mixed|null $handled_by     array
 * @property mixed|null $approved       bool
 * @property mixed|null $approval       array
 * @property mixed|null $handler        array
 */
class CustomerShiftSwap extends Model
{
    protected $path = '/customers/{customer}/shift_swaps';

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
     * Gets the original Employee Model assigned to this shift (if any) from property $from_id
     *
     * @return \Ext\Models\Employee|null
     * @author Torbjørn Kallstad
     */
    public function fromEmployee(): ?Employee
    {
        return $this->from_id ? $this->customer()->getEmployee($this->from_id) : null;
    }

    /**
     * Gets the Employee Model this shift was swapped to from property $to_id
     *
     * @return \Ext\Models\Employee
     * @author Torbjørn Kallstad
     */
    public function toEmployee(): Employee
    {
        return $this->customer()->getEmployee($this->to_id);
    }

    /**
     * Gets CustomerShift Model from property $shift_id
     *
     * @return \Ext\Models\CustomerShift
     * @author Torbjørn Kallstad
     */
    public function shift(): CustomerShift
    {
        return $this->customer()->getShift($this->shift_id);
    }
}
