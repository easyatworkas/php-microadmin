<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasRotationIntervals;

/**
 * @property mixed|null $id             PRIMARY KEY         OK
 * @property mixed|null $customer_id    FOREIGN KEY         OK
 * @property mixed|null $name
 * @property mixed|null $start_day
 * @property mixed|null $days
 * @property mixed|null $employees      WITH/array          OK
 */
class CustomerRotation extends Model
{
    use HasRotationIntervals;

    protected $path = '/customers/{customer}/rotations';

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
     * Gets list of all Employee Models
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function employees(): array
    {
        if (!is_array($this->getAttribute('employees'))) {
            $withEmployees = static::newQuery($this->getPath())
                ->with([ 'employees' ])
                ->get($this->getKey());

            $this->attributes[ 'employees' ] = $withEmployees->getAttribute('employees') ?? [];
        }

        return array_map(function (array $attributes) {
            return Employee::newInstance($attributes);
        }, $this->getAttribute('employees'));
    }
}
