<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $employee_id            FOREIGN KEY     OK
 * @property mixed|null $relationship
 * @property mixed|null $birth_date
 * @property mixed|null $prefix
 * @property mixed|null $first_name
 * @property mixed|null $last_name
 * @property mixed|null $sex
 * @property mixed|null $family_allowance
 * @property mixed|null $family_allowance_code
 * @property mixed|null $relationship_code
 */
class FamilyMember extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/family_members';

    /**
     * Gets Customer Model
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function customer(): Customer
    {
        return Customer::get($this->getIdOf('customer'));
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
