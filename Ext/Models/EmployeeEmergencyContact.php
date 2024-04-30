<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete: TODO: Test/key?
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $employee_id        FOREIGN KEY     OK
 * @property mixed|null $name
 * @property mixed|null $relation
 * @property mixed|null $phone
 * @property mixed|null $employee_name
 */
class EmployeeEmergencyContact extends Model
{
//    protected string $key = '';
    protected $path = '/customers/{customer}/employees/{employee}/emergency_contact';

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
}
