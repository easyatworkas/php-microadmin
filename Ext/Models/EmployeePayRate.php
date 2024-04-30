<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY             OK
 * @property mixed|null $employee_id    FOREIGN KEY             OK
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $rate
 * @property mixed|null $type
 * @property mixed|null $tariff_id      FOREIGN KEY (optional)  OK
 */
class EmployeePayRate extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/pay_rates';

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
     * Gets Tariff Model from optional property $tariff_id
     *
     * @return \Ext\Models\SettingGroupTariff|null
     * @author Torbjørn Kallstad
     */
    public function tariff(): ?SettingGroupTariff
    {
        return is_null($this->tariff_id) ? null : Customer::get($this->getIdOf('customer'))->settingGroup()->getTariff($this->tariff_id);
    }
}
