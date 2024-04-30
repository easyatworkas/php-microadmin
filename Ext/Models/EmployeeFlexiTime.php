<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY             OK
 * @property mixed|null $employee_id            FOREIGN KEY             OK
 * @property mixed|null $business_date
 * @property mixed|null $type                   'auto'|'manual'
 * @property mixed|null $delta
 * @property mixed|null $comment
 * @property mixed|null $performed_by           FOREIGN KEY (optional)  OK
 * @property mixed|null $performed_by_name
 */
class EmployeeFlexiTime extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/flexitime';

    /**
     * Gets Employee Model from property $employee_id
     *
     * @return \Ext\Models\Employee
     * @author Torbjørn Kallstad
     */
    public function employee(): Employee
    {
        return Customer::get($this->getIdOf('customer'))->getEmployee($this->employee_id);
    }

    /**
     * Gets User Model from optional property $performed_by
     *
     * @return \Ext\Models\User|null
     * @author Torbjørn Kallstad
     */
    public function adjustedBy(): ?User
    {
        return is_null($this->performed_by) ? null : User::get($this->performed_by);
    }
}
