<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY     OK
 * @property mixed|null $rotation_id    FOREIGN KEY     OK
 * @property mixed|null $employee_id    FOREIGN KEY     OK
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $rotations      array
 */
class EmployeeRotation extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/rotations';

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
     * Gets CustomerRotation Model from property $rotation_id
     *
     * @return \Ext\Models\CustomerRotation
     * @author Torbjørn Kallstad
     */
    public function rotation(): CustomerRotation
    {
        return Customer::get($this->getIdOf('customer'))->getRotation($this->rotation_id);
    }
}
