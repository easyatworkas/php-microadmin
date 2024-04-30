<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY                 OK
 * @property mixed|null $employee_id        FOREIGN KEY                 OK
 * @property mixed|null $customer_id        FOREIGN KEY                 OK
 * @property mixed|null $in
 * @property mixed|null $out
 * @property mixed|null $business_date
 * @property mixed|null $approved
 * @property mixed|null $approved_by        FOREIGN KEY (optional)      OK
 * @property mixed|null $edited_by_id       FOREIGN KEY (optional)      OK
 * @property mixed|null $time_edited
 * @property mixed|null $manually_opened
 * @property mixed|null $manually_closed
 * @property mixed|null $in_client_id
 * @property mixed|null $out_client_id
 * @property mixed|null $business_unit_id   FOREIGN KEY (optional)      OK
 * @property mixed|null $approved_by_name
 * @property mixed|null $length
 */
class Timepunch extends Model
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

    /**
     * Gets User Model from optional property $approved_by
     *
     * @return \Ext\Models\User|null
     * @author Torbjørn Kallstad
     */
    public function approvedBy(): ?User
    {
        return is_null($this->approved_by) ? null : User::get($this->approved_by);
    }

    /**
     * Gets User Model from optional property $edited_by_id
     *
     * @return \Ext\Models\User|null
     * @author Torbjørn Kallstad
     */
    public function editedBy(): ?User
    {
        return is_null($this->edited_by_id) ? null : User::get($this->edited_by_id);
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
