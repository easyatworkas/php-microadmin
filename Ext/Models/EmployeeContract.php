<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $customer_id        FOREIGN KEY     OK
 * @property mixed|null $employee_id        FOREIGN KEY     OK
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $type_id            FOREIGN KEY     OK
 * @property mixed|null $title
 * @property mixed|null $amount
 * @property mixed|null $amount_type
 * @property mixed|null $week_hours
 * @property mixed|null $month_hours
 * @property mixed|null $year_hours
 * @property mixed|null $percentage
 */
class EmployeeContract extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/contracts';

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
     * Gets SettingGroupContractType Model from property $type_id
     *
     * @return \Ext\Models\SettingGroupContractType
     * @author Torbjørn Kallstad
     */
    public function absenceType(): SettingGroupContractType
    {
        return $this->customer()->settingGroup()->getContractType($this->type_id);
    }
}
