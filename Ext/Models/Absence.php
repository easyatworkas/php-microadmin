<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY         OK
 * @property mixed|null $employee_id        FOREIGN KEY         OK
 * @property mixed|null $type_id            FOREIGN KEY         OK
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $length
 * @property mixed|null $grade
 * @property mixed|null $customer_id        FOREIGN KEY         OK
 */
class Absence extends Model
{
    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function owner(): Customer
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
        return $this->owner()->getEmployee($this->employee_id);
    }

    /**
     * Gets SettingGroup AbsenceType Model from property $type_id
     *
     * @return \Ext\Models\SettingGroupAbsenceType
     * @author Torbjørn Kallstad
     */
    public function absenceType(): SettingGroupAbsenceType
    {
        return $this->owner()->settingGroup()->getAbsenceType($this->type_id);
    }
}
